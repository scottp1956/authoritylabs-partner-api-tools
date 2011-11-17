<?php
 /**
 * alPartner - AuthorityLabs Partner API PHP Library
 *
 * @category Services
 * @package  AuthorityLabs Partner API PHP Library
 * @author   Jonathan Kressaty <jonathan.kressaty@gmail.com> & Brian LaFrance <brian.lafrance@authoritylabs.com>
 * @license  http://creativecommons.org/licenses/MIT/ MIT
 * @link     https://github.com/blafrance/authoritylabs-partner-api-tools
 */
 
class alPartner
{
	private $api_url = 'http://api.authoritylabs.com/keywords/';
	
	/**
	* POST a keyword to the queue
	*
	* @param string $keyword   		Keyword to query against
	* @param string $auth_token		AuthorityLabs Partner API Key
	* @param string $priority		OPTIONAL Defines whether or not to use priority queue. Passing "true" to this will use priority queue
	* @param string $engine			OPTIONAL Search engine to query - see supported engines list at http://authoritylabs.com/api/reference/#engines
	* @param string $locale			OPTIONAL Language/Country code - see supported language/country list at http://authoritylabs.com/api/reference/#countries
	* @param string $pages_from		OPTIONAL Default is false and only works with Google. Defines whether or not to use pages from location results
	* @param string $lang_only		OPTIONAL Default is false and only works with Google. Defines whether or not to use pages in specified language only
	* @param string $callback		OPTIONAL Default is taken from the callback URL that is set in your AuthorityLabs Partner API account. This parameter will override that URL
	*
	*/ 
	public function keywordPost($keyword, $auth_token, $priority="false", $engine="google", $locale="en-US", $pages_from="false", $lang_only="false",$callback=null){
		
		$path = '';
		
		$post_variables = array(
			'keyword' => $keyword,
			'auth_token' => $auth_token,
			'engine' => $engine,
			'locale' => $locale,
			'pages_from' => $pages_from,
			'lang_only' => $lang_only
		);

		if($callback!=null){
			$post_variables['callback'] = $callback;
		}
		
		if($priority=="true"){
			$path = 'priority';		
		}

		return $this->alRequest($post_variables, 'POST', $path);
	}

	/** 
	* GET SERP data for a keyword - returns json or html data of SERP, depending on the specified format
	*
	* @param string $keyword 		Keyword to get data for
	* @param string $auth_token		AuthorityLabs Partner API Key
	* @param string $rank_date		Date to retrieve the SERP for. Must be in YYYY-MM-DD format
	* @param string $data_format	OPTIONAL Define format type to retrieve. Default is json - see supported types at http://authoritylabs.com/api/reference/#formats
	* @param string $engine			OPTIONAL Search engine to query - see supported engines list at http://authoritylabs.com/api/reference/#engines
	* @param string $locale			OPTIONAL Language/Country code - see supported language/country list at http://authoritylabs.com/api/reference/#countries
	* @param string $pages_from		OPTIONAL Default is false and only works with Google. Defines whether or not to use pages from location results
	* @param string $lang_only		OPTIONAL Default is false and only works with Google. Defines whether or not to use pages in specified language only
	*
	*/
	public function keywordGet($keyword, $auth_token, $rank_date, $data_format="json", $engine="google", $locale="en-US", $pages_from="false", $lang_only="false"){
		
		$get_variables = array(
			'keyword' => $keyword,
			'auth_token' => $auth_token,
			'engine' => $engine,
			'locale' => $locale,
			'pages_from' => $pages_from,
			'rank_date' => $rank_date,
			'data_format' => $data_format,
			'lang_only' => $lang_only		
		);
		
		$path = 'get?';
		
		return $this->alRequest($get_variables, 'GET', $path);
	}
	
	/** 
	* GET Ranking for a keyword/domain combo - returns an array with ranking data
	*
	* @param string $url			URL that we want to track rankings for
	* @param string $json_data		json data retrieved using the keywordGet function
	*
	*/
	public function parseRanks($url, $json_data){
		
		$json_data = json_decode($json_data);
		
		$url = str_ireplace('http://','',$url);
		
		if(is_object($json_data)){
			
			$serp = get_object_vars($json_data->serp);
			$arr_rankings = array();
			
			foreach($serp as $key=>$val){
				
				$match = $val->href;
				
				if(stristr($match, '.' . $url))
					$arr_rankings[$key] = $val->href;	

				if(stristr($match, '/' . $url))
					$arr_rankings[$key] = $val->href;
			}
		}
		
		return $arr_rankings;
	}

	/**
	* Request using PHP CURL functions
	* Requires curl library installed and configured for PHP
	* Returns response from the AuthorityLabs Partner API
	*
	* @param array $request_vars	Data for making the request to API
	* @param string $method			Specifies POST or GET method
	* @param string $path			OPTIONAL Path for the API request - specifies priority or get URL when applicable
	*
	*/		
	private function alRequest($request_vars = array(), $method, $path=""){

        $qs = '';
        $response = '';
        
		foreach($request_vars AS $key=>$value)
			$qs .= "$key=". urlencode($value) . '&';
		
		$qs = substr($qs, 0, -1);
		        
        //construct full api url
        $url = $this->api_url . $path;
        if(strtoupper($method) == 'GET')
        	$url .= $qs;
        
		//initialize a new curl object            
		$ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		
		switch(strtoupper($method)) {
            case "GET":
            	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $qs);
                break;
		}
		
		if(FALSE === ($response = curl_exec($ch)))
			return "Curl failed with error " . curl_error($ch); 
		
		//if POST, return response code from API
		if(strtoupper($method) == 'POST')
			$response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				
		curl_close($ch);	

		return $response;
	}
}