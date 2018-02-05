<?php
class ebay{
    //variable instantiation
    private $uri_finding = "http://svcs.ebay.com/services/search/FindingService/v1";
	 private $uri_shopping = 'http://open.api.ebay.com/shopping';
    private $appid = "LBI74e79e-8944-43c7-9fdf-1e0e2e895d4";
    private $version;
    private $format = "JSON";
     
    /**
    * Constructor
    *
    * Sets the eBay version to the current API version
    * 
    */
    public function __construct(){
        $this->version = $this->getCurrentVersion();
		
    }
    
    /**
    * Get Current Version
    *
    * Returns a string of the current eBay Finding API version
    * 
    */
    private function getCurrentVersion(){
        $uri = sprintf("%s?OPERATION-NAME=getVersion&SECURITY-APPNAME=%s&RESPONSE-DATA-FORMAT=%s",
                       $this->uri_finding,
                       $this->appid,
                       $this->format);
        
        $response = json_decode($this->curl($uri));
		
        return $response->getVersionResponse[0]->version[0];
    }
    
    /**
    * Find Products
    *
    * Allows you to search for eBay products based on keyword, product id or
    * keywords (default).  Available values for search_type include
    * findItemsByKeywords, findItemsByCategory, and findItemsByProduct
    * 
    */
    public function findProduct($search_value = '10181', $entries_per_page = 3, $sort='EndTimeSoonest',$storename='',$startpage=1,$categoryid,$filters){
       
        $search_field = "";
		$search_type = 'findItemsByKeywords';
		if($categoryid>0) $search_type='findItemsAdvanced';
		if(!empty($storename)) $search_type='findItemsIneBayStores';
        switch ($search_type){
			case 'findItemsIneBayStores': $search_field = "storeName=". urlencode($storename)."&outputSelector=StoreInfo&keywords=" . urlencode($search_value);
                                        break;
           // case 'findItemsByCategory': $search_field = "categoryId=$search_value";
                                        break;
            //case 'findItemsByProduct':  $search_field = "productId.@type=ReferenceID&productId=$search_value";
                                        //break;
			case 'findItemsAdvanced':	$search_field = "categoryId=$categoryid&keywords=" . urlencode($search_value);
                                        break;						
            case 'findItemsByKeywords':
            default:                    $search_field = "keywords=" . urlencode($search_value);
                                        break;

        }
        
        //build query uri
        $uri = sprintf("%s?OPERATION-NAME=%s&SERVICE-VERSION=%s&siteid=3&SECURITY-APPNAME=%s&RESPONSE-DATA-FORMAT=%s&GLOBAL-ID=EBAY-GB&REST-PAYLOAD&%s&paginationInput.entriesPerPage=%s&sortOrder=%s&paginationInput.pageNumber=%s".$filters,
                        $this->uri_finding,
                        $search_type,
                        $this->version,
                        $this->appid,
                        $this->format,
                        $search_field,
                        $entries_per_page,
						$sort,
						$startpage
						);
        
		//echo $uri;
        return json_decode($this->curl($uri));
    }
    
	public function getCategories(){
		
		$uri = sprintf("%s?responseencoding=%s&callname=GetCategoryInfo&CategoryID=-1&siteid=3&GLOBAL-ID=EBAY-GB&appid=%s&version=729&IncludeSelector=ChildCategories", $this->uri_shopping, $this->format, $this->appid);
		 
        //echo  $uri;
		return json_decode($this->curl($uri));
	}
    public function get($request, $ids, $alt_fields = null){
		
        //determine if requested API call is available from endpoints
        $request_types = array('GetCategoryInfo', 'GetItemStatus', 'GetMultipleItems', 'GetShippingCosts', 'GetSingleItem', 'GetUserProfile');
        if (! in_array($request, $request_types)){
            return 'Invalid request type, please use one of the following: ' . implode(', ', $request_types);
        }
        
        //build out string of alternate parameters that have been supplied to method
        $concat_fields = '';
        if (count($alt_fields) > 0){
            foreach ($alt_fields as $field => $value){
                $concat_fields .= "&$field=$value";
            }
        }
        
        //prepare key field for id search - category, user or item
        $id_field_key = 'ItemID';
        if ($request == 'GetCategoryInfo'){ $id_field_key = 'CategoryID'; }
        if ($request == 'GetUserProfile'){ $id_field_key = 'UserID'; }
        $standard_qstring = sprintf("%s?responseencoding=%s&siteid=3&GLOBAL-ID=EBAY-GB&appid=%s&version=787&IncludeSelector=TextDescription,Variations", $this->uri_shopping, $this->format, $this->appid);
        //build API HTTP request string
        $uri = sprintf("%s&callname=%s&%s=%s%s",
                       $standard_qstring,
					   $request,
                       $id_field_key,
                       $ids,
                       $concat_fields);
        //echo  $uri ;
        return json_decode($this->curl($uri));
    }
    /**
    * cURL
    *
    * Standard cURL function to run GET & POST requests
    * 
    */
    private function curl($url, $method = 'GET', $headers = null, $postvals = null){
        $ch = curl_init($url);
           
        if ($method == 'GET'){
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        } else {
            $options = array(
                CURLOPT_HEADER => true,
                CURLINFO_HEADER_OUT => true,
                CURLOPT_VERBOSE => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $postvals,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_TIMEOUT => 3
            );
            curl_setopt_array($ch, $options);
        }
           
        $response = curl_exec($ch);
        curl_close($ch);
            
        return $response;
    }
}
?>