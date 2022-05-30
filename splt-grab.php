<?php
//define( 'WP_DEBUG', true );
$path = preg_replace('/wp-content.*$/', '', __DIR__);
require_once($path . 'wp-load.php');
require_once($path . 'wp-includes/wp-db.php');
require_once($path . 'wp-admin/includes/taxonomy.php');
require_once('splt-curl.php');

global $wpdb, $curl;
$time = date("Y:m:d H:i:s", time());
$curl = new cURL();

if ($_GET['type'] == "numpage") {
	$urlss = urldecode($_GET['url']);
	$urlss = trim(str_replace(" ", "", $urlss));
	$fetch_type = "";
	if ($_GET['fetchtype'] == "today") {
		$fetch_type = 24;
	}

	$response = vod_json_pagecount($urlss, $fetch_type);
	echo $response;
}

if ($_GET['type'] == "pagedetail") {
	$urlss = urldecode($_GET['url']);
	$urlss = trim(str_replace(" ", "", $urlss));
	$page = $_GET['numpage'];

	$response = vod_data($urlss, $page);
	echo $response;
}

function vod_json_pagecount($url, $fetch_type)
{
	global $curl;
	$url_param = [];
	$url_param['h'] = $fetch_type;
	$url_param['ac'] = 'list';
	if(strpos($url,'?')===false){
		$url .='?';
	}
	else{
		$url .='&';
	}
	$url .= http_build_query($url_param);
	$response = $curl->get($url);

	$response = filter_tags($response);
	$json = json_decode($response,true);
	if (!$json) {
		return json_encode(['code'=>1002, 'msg'=>'Mẫu JSON không đúng, không hỗ trợ thu thập']);
	}
	$url_return = generate_url($url, $fetch_type);
	$array_page = [];
	$array_page['code'] = 1;
	$array_page['page'] = $json['page'];
	$array_page['pagecount'] = $json['pagecount'];
	$array_page['pagesize'] = intval($json['limit']);
	$array_page['recordcount'] = $json['total'];
	$array_page['url'] = $url_return;

	return json_encode($array_page);
}

function generate_url($url, $fetch_type)
{
	$url_param = [];
	$url_param['t'] = '';
	$url_param['h'] = $fetch_type;
	$url_param['ids'] = '';
	$url_param['wd'] = '';
	$url_param['mid'] = 1;
	$url_param['limit'] = 30;
	$url_param['sync_pic_opt'] = 0;
	$url_param['opt'] = 0;
	$url_param['filter'] = 0;
	$url_param['ac'] = 'detail';
	if(strpos($url,'?')===false){
		$url .='?';
	}
	else{
		$url .='&';
	}
	$url .= http_build_query($url_param);
	return $url;
}

function vod_data($url, $page)
{
	global $curl;
	$url_param = [];
	$url_param['pg'] = is_numeric($page) ? $page : '';
	if(strpos($url,'?')===false){
		$url .='?';
	}
	else{
		$url .='&';
	}
	$url .= http_build_query($url_param);
	$result = checkUrl($url);
	if ($result['code'] > 1) {
		return json_encode($result);
	}
	$html = $curl->get($url);
	if (empty($html)) {
		return json_encode(['code'=>1001, 'msg'=>'Liên kết API thất bại, thông thường mạng máy chủ không ổn định ,chết IP,cấm dùng hàm số liên quan']);
	}
	$html = filter_tags($html);
	$json = json_decode($html,true);
	if (!$json) {
		return json_encode(['code'=>1002, 'msg'=>'Mẫu JSON không đúng, không hỗ trợ thu thập']);
	}
	$data = pre_handle($json);
	$msg = handle_data($data);
	return json_encode($msg);
}

/**
 * handle data
 * @param {*} data
 * @returns {array}
 */
function handle_data($data)
{
	global $wpdb;
	$saved_post_count = 0;
	$post_count = count($data);
	$msg = [];
	foreach ($data as $key => $val) {
		// Category
		$genres = explode(',', $val['vod_class']);
		$genres_catid = [];
		if ($genres) {
			foreach ($genres as $valuegenres) {
				$tempcatt = get_category_by_slug($valuegenres);
				if ($tempcatt) {
					$genres_catid[] = $tempcatt->cat_ID;
				} else {
					$genres_catid[] = wp_create_category($valuegenres);
				}
			}
		}
		$time = current_time('mysql');
		$slug = trim($val['vod_en']);
		$title = trim($val['vod_name']);
		$check_dup = $wpdb->get_results("SELECT ID FROM `$wpdb->posts` WHERE `post_name`='$slug'  AND `post_type`='post'");
		$check_dup1 = $wpdb->get_results("SELECT ID FROM `$wpdb->posts` WHERE `post_title`='$title'  AND `post_type`='post'  AND `post_status`='publish'");

		if ($check_dup1[0]) {  // duplicate post title
			if (count($check_dup1)==1) {
				$pidssss=$check_dup1[0]->ID;
				$halim_metabox_options0=$wpdb->get_var( "SELECT `meta_value` FROM `$wpdb->postmeta` WHERE `meta_key`='_halim_metabox_options' AND `post_id`='$pidssss'");
				$halim_metabox_options1=unserialize($halim_metabox_options0);
				
				$dup_ogri_name=$halim_metabox_options1['halim_original_title'];
				if(trim($dup_ogri_name)==trim($val['vod_name'])){
					if($halim_metabox_options1['halim_episode']!=$val['status']['epnow']){
						// dupdup2:
						$pidssss=$check_dup1[0]->ID;
						$halim_metabox_options1['halim_episode']='[ Tập ' . $val['status']['epnow'] . '/' . $val['status']['eptotal'] . ' - End ]';
						$halim_metabox_options1['halim_total_episode']=$val['status']['eptotal'];
						if($val['status']['epnow'] > 1){
							$halim_metabox_options1['halim_movie_formality']='tv_series';
						}
						else{
							$halim_metabox_options1['halim_movie_formality']='single_movies';
						}
						$wpdb->update($wpdb->postmeta, array('meta_value' =>  serialize($halim_metabox_options1)), array( 'post_id' => $pidssss,'meta_key'=>'_halim_metabox_options'));										
						$wpdb->update($wpdb->prefix.'posts', array('post_modified' =>  $time,'post_modified_gmt' =>$time), array( 'ID' => $pidssss));
						// link stream
						$halimmovies2 = json_encode($val['vod_play_list'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
						$wpdb->update($wpdb->postmeta, array('meta_value' =>  serialize($halimmovies2)), array( 'post_id' => $pidssss,'meta_key'=>'_halimmovies'));
						array_push($msg, 'Cập nhật ['.$title.'] thành công');
					} else{
						array_push($msg, 'Trùng lặp bài viết ['.$title.']');
					}
				}
			}
			continue;
		} elseif ($check_dup[0]) {  // duplicate post slug
			$slug = $slug.'-'.$val['vod_year'];
			goto nodup;
		} else {
			nodup:
			$contentsave = array(
				'post_author' => $_GET['author'],
				'post_title' =>  $title,
				'post_name' => $slug,
				'post_content' => $val['vod_content'],
				'post_status' => 'publish',
				'post_type' =>  'post',
				'post_date' => $time,
				'post_date_gmt' => $time,
				'post_modified' => $time,
				'post_modified_gmt' => $time
			);
			$wpdb->insert($wpdb->prefix . 'posts', $contentsave, array('%s', '%d'));
			$post_id = $wpdb->insert_id;
			wp_set_post_terms($post_id, $genres_catid, 'category', false);
			$wpdb->update($wpdb->prefix . 'posts', array('post_title' =>  $title, 'guid' => get_site_url() . '?p=' . $post_id), array('ID' => $post_id));
			$saved_post_count ++;
			array_push($msg, 'Thu thập thành công ['.$title.']');

			// Update post thumb to post_meta
			$thumb_id = nguontv_generate_post_thumb($val['vod_pic'], $post_id);
			if ($thumb_id['id'] > 0) {
				update_post_meta($post_id, '_thumbnail_id', $thumb_id['id']);
			}

			// Update link stream to post_meta
			$result = vod_video($val['vod_play_list'], $post_id);
			if ($result == 'error') {
				array_push($msg, 'Bài viết ['.$post_id.'] Link phát bị lỗi');
			}

			$halim_metabox_options0 = 'a:21:{s:21:"halim_movie_formality";s:13:"single_movies";s:18:"halim_movie_status";s:7:"ongoing";s:14:"fetch_info_url";s:0:"";s:16:"halim_poster_url";b:0;s:15:"halim_thumb_url";s:0:"";s:20:"halim_original_title";b:0;s:17:"halim_trailer_url";s:0:"";s:13:"halim_runtime";s:0:"";s:12:"halim_rating";s:0:"";s:11:"halim_votes";s:0:"";s:13:"halim_episode";b:0;s:19:"halim_total_episode";b:0;s:13:"halim_quality";s:0:"";s:18:"halim_movie_notice";s:0:"";s:21:"halim_showtime_movies";s:0:"";s:19:"halim_add_to_widget";b:0;s:17:"save_poster_image";b:0;s:18:"set_reatured_image";b:0;s:12:"save_all_img";b:0;s:8:"is_adult";b:0;s:12:"is_copyright";b:0;}';
			$halim_metabox_options1 = unserialize($halim_metabox_options0);

			$halim_metabox_options1['halim_movie_formality'] = 'tv_series';
			$halim_metabox_options1['halim_movie_status'] = $val['status']['status'];
			$halim_metabox_options1['fetch_info_url'] = '';
			$halim_metabox_options1['halim_poster_url'] = $thumb_id['url'];
			$halim_metabox_options1['halim_thumb_url'] = '';
			$halim_metabox_options1['halim_original_title'] = trim($val['vod_name']);
			$halim_metabox_options1['halim_trailer_url'] = '';
			$halim_metabox_options1['halim_runtime'] = '';
			$halim_metabox_options1['halim_rating'] = '';
			$halim_metabox_options1['halim_votes'] = '';
			$halim_metabox_options1['halim_episode'] = '[ Tập ' . $val['status']['epnow'] . '/' . $val['status']['eptotal'] . ' - End ]';
			$halim_metabox_options1['halim_total_episode'] = $val['status']['eptotal'];
			$halim_metabox_options1['halim_quality'] = $val['vod_version'];
			$halim_metabox_options1['halim_movie_notice'] = '';
			$halim_metabox_options1['halim_showtime_movies'] = '';
			$halim_metabox_options1['save_poster_image'] = false;
			$halim_metabox_options1['set_reatured_image'] = false;
			$halim_metabox_options1['save_all_img'] = false;
			$halim_metabox_options1['is_adult'] = false;
			$halim_metabox_options1['is_copyright'] = false;
			if ($val['status']['epnow'] > 1) {
				$halim_metabox_options1['halim_movie_formality'] = 'tv_series';
			} else {
				$halim_metabox_options1['halim_movie_formality'] = 'single_movies';
			}
			if ($val['status']['epnow'] == 1) {
				$halim_metabox_options1['halim_runtime'] = '' . $val['status']['minutes'] . ' Phút';
			} else {
				$halim_metabox_options1['halim_runtime'] = "";
			}
			$metaid = add_post_meta($post_id, '_halim_metabox_options', $halim_metabox_options1);

			$actor = explode(',', $val['vod_actor']);
			$couuuu = count($actor);
			for ($ll = 0; $ll < $couuuu - 1; $ll++) {
				wp_set_object_terms($post_id, array('actor' => trim($actor[$ll])), 'actor', true);
			}
			wp_set_object_terms($post_id, array('director' => $val['vod_director']), 'director');
			wp_set_object_terms($post_id, array('country' => $val['vod_area']), 'country');
			wp_set_object_terms($post_id, array('release' => $val['vod_year']), 'release');

			if (isset($halim_metabox_options1['halim_movie_formality'])) {
				if ($halim_metabox_options1['halim_movie_formality'] == 'single_movies') {
					$dkm_post_f = 'post-format-aside';
				} elseif ($halim_metabox_options1['halim_movie_formality'] == 'tv_series') {
					$dkm_post_f = 'post-format-gallery';
				} elseif ($halim_metabox_options1['halim_movie_formality'] == 'tv_shows') {
					$dkm_post_f = 'post-format-video';
				} elseif ($halim_metabox_options1['halim_movie_formality'] == 'theater_movie') {
					$dkm_post_f = 'post-format-audio';
				} else {
					$dkm_post_f = 'post-format-aside';
				}
			}
			wp_set_object_terms($post_id, array('post_format' => $dkm_post_f), 'post_format');
		}
	}
	return ['code' => 1, 'msg' => $msg, 'postcount' => $post_count, 'saved_post' => $saved_post_count];
}

/**
 * post link stream
 * @param {*} data
 * @param {int} post_id
 */
function vod_video($data, $post_id)
{
	$halimmovies2 = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	$metaid = add_post_meta($post_id, '_halimmovies', $halimmovies2);
	return $metaid > 0 ? 'done' : 'error';
}

/**
 * pre handle data
 * @param {*} data
 * @returns {array}
 */
function pre_handle($data)
{
	if ($data['code'] > 1) {
		return;
	}
	$res_list = [];

	foreach($data['list'] as $k => $v) {
		$v['vod_name'] = html_entity_decode($v['vod_name']);
		$v['vod_en'] = sanitize_title($v['vod_name']);
		$v['vod_letter'] = strtoupper(substr($v['vod_en'],0,1));
		$v['vod_class'] = mac_txt_merge($v['vod_class'],$v['type_name']);
		$v['vod_actor'] = mac_format_text($v['vod_actor']);
		$v['vod_director'] = mac_format_text($v['vod_director']);
		$v['vod_content'] = html_entity_decode($v['vod_content']);
		$v['vod_play_list'] = mac_play_list($v['vod_play_from'], $v['vod_play_url'], $v['vod_play_server'], $v['vod_play_note'], 'play');
		$v['status'] = vod_status($v['vod_remarks'], $v['vod_weekday']);
		$v['vod_year'] = ($v['vod_year'] == '' || $v['vod_year'] == 0) ? 'NA' : $v['vod_year'];

		unset($v['vod_time'], $v['vod_time_add'], $v['vod_weekday'], $v['vod_status']);
		unset($v['vod_hits'], $v['vod_hits_day'], $v['vod_hits_week'], $v['vod_hits_month']);
		unset($v['vod_up'], $v['vod_down'], $v['vod_score_num'], $v['vod_score_all'], $v['vod_score']);
		unset($v['group_id'], $v['vod_copyright'], $v['vod_duration'], $v['vod_lang']);
		unset($v['type_id'], $v['type_id_1'], $v['vod_author'], $v['vod_behind'], $v['vod_color'], $v['vod_douban_id'], $v['vod_douban_score']);
		unset($v['vod_down_from'], $v['vod_down_note'], $v['vod_down_server'], $v['vod_down_url']);
		unset($v['vod_id'], $v['vod_isend'], $v['vod_jumpurl'], $v['vod_level'], $v['vod_lock']);
		unset($v['vod_play_note'], $v['vod_play_server'], $v['vod_play_url'], $v['vod_play_from']);
		unset($v['vod_plot'], $v['vod_plot_detail'], $v['vod_plot_name']);
		unset($v['vod_pic_screenshot'], $v['vod_pic_thumb'], $v['vod_pubdate']);
		unset($v['vod_pwd'], $v['vod_pwd_down'], $v['vod_pwd_down_url'], $v['vod_pwd_play'], $v['vod_pwd_play_url'], $v['vod_pwd_url']);
		unset($v['vod_rel_art'], $v['vod_rel_vod'], $v['vod_reurl'], $v['vod_serial'], $v['vod_state'], $v['vod_sub'], $v['vod_tag']);
		unset($v['vod_total'], $v['vod_tpl'], $v['vod_tpl_down'], $v['vod_tpl_play'], $v['vod_trysee'], $v['vod_tv'], $v['vod_writer']);
		unset($v['vod_points'], $v['vod_points_down'], $v['vod_points_play'], $v['vod_time_hits'], $v['vod_time_make'], $v['vod_tv'], $v['vod_writer']);
		
		$res_list[$k] = $v;
	}
	return $res_list;
}

function vod_status($remarks, $duration)
{
	$remarks = mb_strtolower($remarks, "UTF-8");
	$status = [];
	if ( strpos($remarks, "hd") !== false ) {
		$status['status'] = 'completed';
		$status['epnow'] = 1;
		$status['eptotal'] = 1;
		$status['minutes'] = preg_replace('/[^0-9]/', '', $duration) == "" ? "NA" : preg_replace('/[^0-9]/', '', $duration);
	} elseif ( strpos($remarks, 'tập đang chiếu') !== false || strpos($remarks, 'cập nhật đến') !== false ) {
		$epnow = preg_replace('/[^0-9]/', '', $remarks);
		$status['status'] = 'ongoing';
		$status['epnow'] = $epnow;
		$status['eptotal'] = '';
		$status['minutes'] = '';
	} elseif (preg_match('/^t.*\d+?\//', $remarks)) {
		preg_match('/(\d+?)\/(.*)$/', $remarks, $matches);
		$status['epnow'] = intval($matches[1]);
		$status['eptotal'] = $matches[2];
		$status['status'] = $matches[1] == $matches[2] ? 'completed' : 'ongoing';
		$status['minutes'] = '';
	} else {
		$status['status'] = 'completed';
		$status['epnow'] = 1;
		$status['eptotal'] = 1;
		$status['minutes'] = "NA";
	}
	return $status;
}

function mac_play_list($vod_play_from, $vod_play_url, $vod_play_server, $vod_play_note)
{
    $vod_play_from_list = [];
    $vod_play_url_list = [];

    if(!empty($vod_play_from)) {
        $vod_play_from_list = explode('$$$', $vod_play_from);
    }
    if(!empty($vod_play_url)) {
        $vod_play_url_list = explode('$$$', $vod_play_url);
    }

    $res_list = [];
    foreach($vod_play_from_list as $key=>$val){
		$res_list_1['halimmovies_server_name'] = $val;
		$array_url = explode('#',$vod_play_url_list[$key]);
		foreach($array_url as $k=>$v){
			if(empty($val)) continue;
			list($title, $url, $from) = explode('$', $v);
			$adddata = array(
				'halimmovies_ep_name' => trim($title),
				'halimmovies_ep_slug' => sanitize_title($title),
				'halimmovies_ep_type' => strpos($url, "m3u8") === false ? "embed" : "link",
				'halimmovies_ep_link' => $url,
				'halimmovies_ep_subs' => null,
				'halimmovies_ep_listsv' => null
			);
			$is_slug = str_replace("-", "_", sanitize_title(trim($title)));
			$res_list_1['halimmovies_server_data'][$is_slug] = $adddata;
		}
		array_push($res_list, $res_list_1);
    }
	// $res_list = json_encode($res_list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $res_list;
}

function mac_txt_merge($txt,$str)
{
    if(empty($str)){
        return $txt;
    }
    $txt = mac_format_text($txt);
    $str = mac_format_text($str);
    $arr1 = $txt == "NA" ? [] : explode(',',$txt);
    $arr2 = explode(',',$str);
    $arr = array_merge($arr1,$arr2);
    return join(',',array_unique(array_filter($arr)));
}

function mac_format_text($str)
{
	if (empty($str)) {
		return "NA";
	}
	$str = str_replace(array('/','，','|','、',',,,'),',',$str);
	$splt_str = explode(',', $str);
	foreach ($splt_str as &$val) {
		$val = ucwords(trim($val));
	}
	unset($val);
    return implode(',', $splt_str);
}

function checkUrl($url)
{
	$result = parse_url($url);
	if (empty($result['host']) || in_array($result['host'], ['127.0.0.1', 'localhost'])) {
		return ['code' => 1001, 'msg' => 'API liên kết sai hoặc không thể liên kết với localhost'];
	}
	return ['code' => 1];
}

function filter_tags($rs)
{
    $rex = array('{:','<script','<iframe','<frameset','<object','onerror');
    if(is_array($rs)){
        foreach($rs as $k2=>$v2){
            if(!is_numeric($v2)){
                $rs[$k2] = str_ireplace($rex,'*',$rs[$k2]);
            }
        }
    }
    else{
        if(!is_numeric($rs)){
            $rs = str_ireplace($rex,'*',$rs);
        }
    }
    return $rs;
}

function nguontv_generate_post_thumb($imageUrl, $post_id)
{
	global $curl, $wpdb;
	$imageUrl = $imageUrl;
	$filename = 'pic-nguontv-' . $post_id . '.jpg';
	if (!(($uploads = wp_upload_dir(current_time('mysql'))) && false === $uploads['error'])) {
		return "thumb fail:uploads dir";
	}
	$filename = wp_unique_filename($uploads['path'], $filename);
	$new_file = $uploads['path'] . "/$filename";

	$file_data_header = explode("/n", $curl->getheader($imageUrl));
	$file_data = $curl->get($imageUrl);

	if (!preg_match('#200#is', $file_data_header[0], $kbjhvkfbavfk)) {
		return "thumb fail:" . $file_data_header[0];
	}
	file_put_contents($new_file, $file_data);
	$stat = stat(dirname($new_file));
	$perms = $stat['mode'] & 0000666;
	@chmod($new_file, $perms);
	$wp_filetype = wp_check_filetype($filename, @$mimes);
	extract($wp_filetype);
	if ((!$type || !$ext) && !current_user_can('unfiltered_upload')) {
		return "thumb fail:file type";
	}
	$url = $uploads['url'] . "/$filename";
	$attachment = array(
		'post_mime_type' => $type,
		'guid' => $url,
		'post_parent' => null,
		'post_title' => $filename,
		'post_content' => '',
	);
	$thumb_id['id'] = wp_insert_attachment($attachment, @$file, $post_id);
	$thumb_id['url'] = str_replace($_SERVER['SERVER_ADDR'], "", strstr($url, $_SERVER['SERVER_ADDR']));

	if (!is_wp_error($thumb_id)) {
		require_once(ABSPATH . '/wp-admin/includes/image.php');
		wp_update_attachment_metadata($thumb_id['id'], wp_generate_attachment_metadata($thumb_id['id'], $new_file));
		update_attached_file($thumb_id['id'], $new_file);
		return $thumb_id;
	}
	return "thumb fail:is_wp_error";;
}
