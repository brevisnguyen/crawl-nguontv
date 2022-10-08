<?php

/**
 * The public-facing functionality of the plugin.
 * 
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 */
class Nguon_Movies_Crawler {
    private $plugin_name;
    private $version;

    /**
	 * Initialize the class and set its properties.
	 *
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_nguon_scripts() {
        wp_enqueue_script( $this->plugin_name . 'nguontvjs', plugin_dir_url( __FILE__ ) . 'js/nguontv.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script( $this->plugin_name . 'bootstrapjs', plugin_dir_url( __FILE__ ) . 'js/bootstrap.bundle.min.js', array(), $this->version, false );
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_nguon_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/nguontv.css', array(), $this->version, 'all' );
    }

    /**
	 * Make CURL
	 *
	 * @param  string      $url       Url string
	 * @return string|bool $response  Response
	 */
    private function curl($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    /**
     * Get image via CURL
     */
    private function img_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);       
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
	 * wp_ajax_nguon_crawler_api action Callback function
	 *
	 * @param  string $api url
	 * @return json $page_array
	 */
    public function nguon_crawler_api()
    {
        $url = $_POST['api'];
        $url = strpos($url, '?') === false ? $url .= '?' : $url .= '&';
        $full_url = $url . http_build_query(['ac' => 'list', 'limit' => 30, 'pg' => 1]);
        $latest_url = $url . http_build_query(['ac' => 'list', 'limit' => 30, 'pg' => 1, 'h' => 24]);

        $full_response = $this->curl($full_url);
        $latest_response = $this->curl($latest_url);

        $data = json_decode($full_response);
        $latest_data = json_decode($latest_response);
        if ( !$data ) {
            echo json_encode(['code' => 999, 'message' => 'Mẫu JSON không đúng, không hỗ trợ thu thập']);
            die();
        }
        $input_dom = '<div class="form-check form-check-inline removeable"><input class="form-check-input mt-0" type="radio" name="type_id" value="{type_id}" id="type_{type_id}"><label class="form-check-label" for="type_{type_id}">{type_name}</label></div>';
        $html = '';
        foreach ( $data->class as $type ) {
            $html .= str_replace(['{type_id}', '{type_name}'], [$type->type_id, $type->type_name], $input_dom);
        }
        $page_array = array(
            'code'              => 1,
            'last_page'         => $data->pagecount,
            'update_today'      => $latest_data->total,
            'total'             => $data->total,
            'type_list'         => $html,
            'full_list_page'    => range(1, $data->pagecount),
            'latest_list_page'  => range(1, $latest_data->pagecount),
        );
        echo json_encode($page_array);

        wp_die();
    }

    /**
	 * wp_ajax_nguon_get_movies_page action Callback function
	 *
	 * @param  string $api        url
	 * @param  string $param      query params
	 * @return json   $page_array List movies in page
	 */
    public function nguon_get_movies_page()
    {
        try 
        {
            $url = $_POST['api'];
            $params = $_POST['param'];
            $type_id = $_POST['type_id'];
            
            $url = strpos($url, '?') === false ? $url .= '?' : $url .= '&';
            if ( $type_id !== null || $type_id !== '0' ) {
                $params .= '&t=' . $type_id;
            }
            $response = $this->curl($url . $params);

            $data = json_decode($response);
            if ( !$data ) {
                echo json_encode(['code' => 999, 'message' => 'Mẫu JSON không đúng, không hỗ trợ thu thập']);
                die();
            }
            $page_array = array(
                'code'          => 1,
                'movies'        => $data->list,
            );
            echo json_encode($page_array);
    
            wp_die();
        } catch (\Throwable $th) {
            //throw $th;
            echo json_encode(['code' => 999, 'message' => $th]);
            wp_die();
        }
    }

    /**
	 * wp_ajax_nguon_crawl_by_id action Callback function
	 *
	 * @param  string $api        url
	 * @param  string $param      movie id
	 */
    public function nguon_crawl_by_id()
    {
        $url = $_POST['api'];
        $params = $_POST['param'];
        $url = strpos($url, '?') === false ? $url .= '?' : $url .= '&';
        $response = $this->curl($url . $params);

        $response = $this->filter_tags($response);
        $data = json_decode($response, true);
        if ( !$data ) {
            echo json_encode(['code' => 999, 'message' => 'Mẫu JSON không đúng, không hỗ trợ thu thập']);
            die();
        }
        $movie_data = $this->refined_data($data['list']);

        $args = array(
			'post_type' => 'post',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => '_nguontv_id',
					'value' => $movie_data['movie_id'],
				)
			)
		);
        $wp_query = new WP_Query($args);
        if ( $wp_query->have_posts() ) { // Trùng tên phim
            while ($wp_query->have_posts()) {
                $wp_query->the_post();
                global $post;
                $_halim_metabox_options = get_post_meta($post->ID, '_halim_metabox_options', true);

                if($_halim_metabox_options["halim_episode"] == $movie_data['episode']) { // Tập phim không thay đổi
                    $result = array(
                        'code' => 999,
                        'message' => $movie_data['org_title'] . ' : Không cần cập nhật',
                    );
                    echo json_encode($result);
                    wp_die();
                }

                $_halim_metabox_options["halim_movie_formality"] = $movie_data['type'];
                $_halim_metabox_options["halim_movie_status"] = strtolower($movie_data['status']);
                $_halim_metabox_options["halim_original_title"] = $movie_data['org_title'];
                $_halim_metabox_options["halim_runtime"] = $movie_data['duration'];
                $_halim_metabox_options["halim_episode"] = $movie_data['episode'];
                $_halim_metabox_options["halim_total_episode"] = '';
                $_halim_metabox_options["halim_quality"] = $movie_data['lang'] . ' - ' . $movie_data['quality'];
                update_post_meta($post->ID, '_halim_metabox_options', $_halim_metabox_options);

                update_post_meta($post->ID, '_halimmovies', json_encode($movie_data['episodes'], JSON_UNESCAPED_UNICODE));
                $result = array(
                    'code' => 1,
                    'message' => $movie_data['org_title'] . ' : Cập nhật thành công.',
                );
                echo json_encode($result);
                wp_die();
            }
        }

        $post_id = $this->insert_movie($movie_data);
        update_post_meta($post_id, '_halimmovies', json_encode($movie_data['episodes'], JSON_UNESCAPED_UNICODE));

        $result = array(
			'code' => 1,
			'message' => $movie_data['org_title'] . ' : Thu thập thành công.',
		);
        echo json_encode($result);
        wp_die();
    }

    /**
	 * Refine movie data from api response
	 *
	 * @param  array  $array_data   raw movie data
	 * @param  array  $movie_data   movie data
	 */
    private function refined_data($array_data)
    {
        foreach ($array_data as $key => $data) {
            if($data['type_id'] == 1) {
                $type = "single_movies";
                $duration = $data['vod_weekday'];
                $status = 'completed';
            } else {
                $type	= "tv_series";
                if ( strpos($data['vod_remarks'], 'tập đang chiếu') !== false || strpos($data['vod_remarks'], 'cập nhật đến') !== false ) {
                    $status = 'ongoing';
                } else {
                    $status = 'completed';
                }
            }

            $categories = array_merge($this->format_text($data['type_name']), $this->format_text($data['vod_class']));
            $tags = [];
            array_push($tags, sanitize_text_field($data['vod_name']));
            $tags = array_merge($tags, $this->format_text($data['vod_class']));
    
            $movie_data = [
                'title' => $data['vod_name'],
                'org_title' => ($data['vod_en'] == trim($data['vod_en']) && strpos($data['vod_en'], ' ') !== false) ? $data['vod_en'] : $data['vod_name'],
                'pic_url' => $data['vod_pic'],
                'actor' => $this->format_text($data['vod_actor']),
                'director' => $this->format_text($data['vod_director']),
                'episode' => $type == 'single_movies' ? 'Full' : $data['vod_remarks'],
                'episodes' => $this->get_play_url($data['vod_play_from'], $data['vod_play_note'], $data['vod_play_url']),
                'country' => $data['vod_area'],
                'language' => 'Vietsub',
                'year' => $data['vod_year'],
                'content' => preg_replace('/\\r?\\n/s', '', $data['vod_content']),
                'tags' => $tags,
                'quality' => ['HD', '1080P', '720P'][random_int(0, 2)],
                'type' => $type,
                'categories' => $categories,
                'duration' => $duration,
                'status' => $status,
                'movie_id' => $data['vod_id'],
            ];
        }
        return $movie_data;
    }

    /**
	 * Insert movie to WP posts, save images
	 *
	 * @param  array  $data   movie data
	 */
    private function insert_movie($data)
    {
        $categories_id = [];
        foreach ($data['categories'] as $category) {
            if (!category_exists($category) && $category !== '') {
                wp_create_category($category);
            }
            $categories_id[] = get_cat_ID($category);
        }
        foreach ($data['tags'] as $tag) {
            if (!term_exists($tag) && $tag != '') {
                wp_insert_term($tag, 'post_tag');
            }
        }

        $post_data = array(
            'post_title'   		=> $data['title'],
            'post_content' 		=> $data['content'],
            'post_status'  		=> 'publish',
            'comment_status' 	=> 'closed',
            'ping_status'  		=> 'closed',
            'post_author'  		=> get_current_user_id()
        );
        $post_id = wp_insert_post($post_data);

        $results = $this->save_images($data['pic_url'], $post_id, true);
        wp_set_object_terms($post_id, $data['status'], 'status', false);

        $post_format = halim_get_post_format_type($data['type']);
        set_post_format($post_id, $post_format);

        $post_meta_movies = array(
            'halim_movie_formality' => $data['type'],
            'halim_movie_status' => strtolower($data['status']),
            'halim_poster_url' => $results['url'],
            'halim_thumb_url' => $results['url'],
            'halim_original_title' => $data['org_title'],
            'halim_trailer_url' => '',
            'halim_runtime' => $data['duration'],
            'halim_rating' => '',
            'halim_votes' => '',
            'halim_episode' => $data['episode'],
            'halim_total_episode' => '',
            'halim_quality' => $data['language'] . ' - ' . $data['quality'],
            'halim_movie_notice' => '',
            'halim_showtime_movies' => '',
            'halim_add_to_widget' => false,
            'save_poster_image' => false,
            'set_reatured_image' => false,
            'save_all_img' => false,
            'is_adult' => false,
            'is_copyright' => false,
        );

        $default_episode = array();
        $ep_sv_add['halimmovies_server_name'] = "Server #Embed";
        $ep_sv_add['halimmovies_server_data'] = array();
        array_push($default_episode, $ep_sv_add);

        wp_set_object_terms($post_id, $data['director'], 'director', false);
        wp_set_object_terms($post_id, $data['actor'], 'actor', false);
        wp_set_object_terms($post_id, sanitize_text_field($data['year']), 'release', false);
        wp_set_object_terms($post_id, $data['country'], 'country', false);
        wp_set_post_terms($post_id, $data['tags']);
        wp_set_post_categories($post_id, $categories_id);
        update_post_meta($post_id, '_halim_metabox_options', $post_meta_movies);
        update_post_meta($post_id, '_halimmovies', json_encode($default_episode, JSON_UNESCAPED_UNICODE));
        update_post_meta($post_id, '_edit_last', 1);
        add_post_meta($post_id, '_nguontv_id', $data['movie_id']);
        return $post_id;
    }

    /**
	 * Save movie thumbail to WP
	 *
	 * @param  string   $image_url   thumbail url
	 * @param  int      $post_id     post id
	 * @param  bool     $set_thumb   set thumb
	 */
    public function save_images($image_url, $post_id, $set_thumb = false)
    {
        require_once( ABSPATH . "/wp-admin/includes/file.php");

        $temp_file = download_url( $image_url );
        if ( ! is_wp_error( $temp_file ) ) {

            $mime_extensions = array(
                'jpg'          => 'image/jpg',
                'jpeg'         => 'image/jpeg',
                'gif'          => 'image/gif',
                'png'          => 'image/png',
                'webp'         => 'image/webp',
            );
            $file = array(
                'name'     => basename($image_url), // ex: wp-header-logo.png
                'type'     => $mime_extensions[pathinfo( $image_url, PATHINFO_EXTENSION )],
                'tmp_name' => $temp_file,
                'error'    => 0,
                'size'     => filesize( $temp_file ),
            );
            $overrides = array(
                'test_form' => false,
                'test_size' => true,
                'test_upload' => true,
            );
            $results = wp_handle_sideload( $file, $overrides );
        
            if ( ! empty( $results['error'] ) ) {
                // Insert any error handling here.
            } else {
                $attachment = array(
                    'guid' => $results['url'],
                    'post_mime_type' => $results['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($results['file'])),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $results['file'], $post_id);

                if ( $set_thumb != false ) {
                    set_post_thumbnail($post_id, $attach_id);
                }

                return $results;
            }
        }
    }

    /**
	 * Uppercase the first character of each word in a string
	 *
	 * @param  string   $string     format string
	 * @param  array    $arr        string array
	 */
    private function format_text($string)
    {
        $string = str_replace(array('/','，','|','、',',,,'),',',$string);
        $arr = explode(',', sanitize_text_field($string));
        foreach ($arr as &$item) {
            $item = ucwords(trim($item));
            $item = mb_strtoupper(mb_substr($item, 0, 1)).mb_substr($item, 1, mb_strlen($item));
        }
        return $arr;
    }

    /**
	 * Filter html tags in api response
	 *
	 * @param  string   $rs     response
	 * @param  array    $rs     response
	 */
    private function filter_tags($rs)
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

    /**
	 * Get eposide url
	 *
	 * @param  string    $servers_str
	 * @param  string    $note
	 * @param  string    $urls_str
	 */
    private function get_play_url($servers_str, $note, $urls_str)
    {
        $server_add = array();
        list($embed_links, $hsl_links) = explode($note, $urls_str); // [embed, hsl]
        $server_info["halimmovies_server_name"] = 'Vietsub #1';
        $server_info["halimmovies_server_data"] = array();
        $episodes = explode('#', $hsl_links);
        $episodes_sub = explode('#', $embed_links);

        foreach ($episodes as $key => $value) {
            $extract_url_pattern = "/https?:\\/\\/(?:www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{1,256}\\.[a-zA-Z0-9()]{1,6}\\b(?:[-a-zA-Z0-9()@:%_\\+.~#?&\\/=]*)/";
            preg_match_all($extract_url_pattern, $value, $matches);
            if ( $matches ) {
                $ep_link = str_replace('http://', 'https://', $matches[0][0]);
                $ep_name = count($episodes) > 1 ? $key + 1 : 'Full';
                $_slug = sanitize_title($ep_name);
                $server_info['halimmovies_server_data'][$_slug]['halimmovies_ep_name'] = $ep_name;
                $server_info['halimmovies_server_data'][$_slug]['halimmovies_ep_slug'] = sanitize_title($ep_name);
                $server_info['halimmovies_server_data'][$_slug]['halimmovies_ep_type'] = 'link';
                $server_info['halimmovies_server_data'][$_slug]['halimmovies_ep_link'] = $ep_link;
                $server_info['halimmovies_server_data'][$_slug]['halimmovies_ep_subs'] = null;
                $server_info['halimmovies_server_data'][$_slug]['halimmovies_ep_listsv'] = array();

                preg_match_all($extract_url_pattern, $episodes_sub[$key], $sub_matches);
                $ep_sub_link = str_replace('http://', 'https://', $sub_matches[0][0]);
                $varSub['halimmovies_ep_listsv_link'] = $ep_sub_link;
                $varSub['halimmovies_ep_listsv_name'] = 'Dự phòng';
                $varSub['halimmovies_ep_listsv_type'] = 'embed';
                array_push($server_info['halimmovies_server_data'][$_slug]['halimmovies_ep_listsv'], $varSub);
            }
        }
        array_push($server_add, $server_info);
        return $server_add;
    }
}
