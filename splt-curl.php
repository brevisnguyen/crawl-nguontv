<?php
	class cURL {
		var $headers;
		var $user_agent;
		var $compression;
		var $cookie_file;
		var $proxy;
		function __construct($cookies=TRUE,$cookie='cookietvhay.txt',$compression='gzip',$proxy='') {
			$this->headers[] = 'Accept: image/gif, image/x-bitmap, image/jpeg, image/pjpeg';
			$this->headers[] = 'Connection: Keep-Alive';
			$this->headers[] = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
			// $this->user_agent = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.95 Safari/537.36';
			// $this->user_agent = 'Mozilla/5.0 (Linux; Android 5.1.1; Nexus 4 Build/LMY48T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.89 Mobile Safari/537.36';
			// $this->user_agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 9_2_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13D15 Safari/601.1';
			$this->user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36 OPR/38.0.2220.41';
			$this->compression=$compression;
			$this->proxy=$proxy;
			$this->cookies=$cookies;
			if ($this->cookies == TRUE) $this->cookie($cookie);
		}
		function cookie($cookie_file) {
			if (file_exists($cookie_file)) {
				$this->cookie_file=$cookie_file;
				} else {
				fopen($cookie_file,'w') or $this->error('The cookie file could not be opened. Make sure this directory has the correct permissions');
				$this->cookie_file=$cookie_file;
				fclose($this->cookie_file);
			}
		}
		
		function getheader($url) {
			$process = curl_init($url);
			curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
			curl_setopt($process, CURLOPT_HEADER, 1);
			curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
			if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
			if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
			curl_setopt($process,CURLOPT_ENCODING , $this->compression);
			curl_setopt($process, CURLOPT_TIMEOUT, 30);
			curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
			//curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($process,CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($process,CURLOPT_CAINFO, NULL);
			curl_setopt($process,CURLOPT_CAPATH, NULL);
			$return = curl_exec($process);
			curl_close($process);
			return $return;
		}
		
		function get($url) {
			$process = curl_init($url);	
			curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
			curl_setopt($process, CURLOPT_HEADER, 0);
			curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
			if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
			if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
			curl_setopt($process,CURLOPT_ENCODING , $this->compression);
			curl_setopt($process, CURLOPT_TIMEOUT, 30);
			curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
			//curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($process,CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt($process,CURLOPT_CAINFO, NULL); 
			curl_setopt($process,CURLOPT_CAPATH, NULL);
			$return = curl_exec($process);
			curl_close($process);
			return $return;
		}
		function post($url,$data) {
			$process = curl_init($url);
			curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
			curl_setopt($process, CURLOPT_HEADER, 1);
			curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
			if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
			if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
			curl_setopt($process, CURLOPT_ENCODING , $this->compression);
			curl_setopt($process, CURLOPT_TIMEOUT, 30);
			curl_setopt($process, CURLOPT_POSTFIELDS, $data);
			curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($process, CURLOPT_POST, 1);
			curl_setopt($process,CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt($process,CURLOPT_CAINFO, NULL); 
			curl_setopt($process,CURLOPT_CAPATH, NULL); 
			$return = curl_exec($process);
			curl_close($process);
			return $return;
		}
		function error($error) {
			echo "<center><div style='width:500px;border: 3px solid #FFEEFF; padding: 3px; background-color: #FFDDFF;font-family: verdana; font-size: 10px'><b>cURL Error</b><br>$error</div></center>";
			die;
		}

		
	}
