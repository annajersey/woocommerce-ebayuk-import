<?php
/*
Plugin Name: WooCommerce Ebay UK Import
Description: Ebay affiliate plugin that import products from eBay UK to woocommerce
Version: 1.0

*/

	
	if ( ! class_exists( 'WC_EbayUKImport' ) ) {
		
		class WC_EbayUKImport {
			
			public $textdomain = 'wc_ebayukimport';
			public function __construct() {
				global $product;
				
				register_activation_hook(__FILE__, array( &$this, 'plugin_activate' ));
				add_action('admin_menu',array( &$this, 'register_my_custom_submenu' ) ,99);
				add_action( 'admin_action_importproduct', array( &$this, 'importproduct_admin_action' ) );
				add_action( 'woocommerce_single_product_summary', array( &$this, 'woocommerce_template_single_ebayuk' ), 500 );
				add_filter( 'manage_edit-product_columns', array( &$this, 'product_new_column' ),15 );
				add_action( 'manage_product_posts_custom_column',array( &$this, 'update_ebay_price' ) , 10, 2 );
				add_action( 'admin_action_updateebayprice', array( &$this, 'updateebayprice_admin_action' ) );
				
				add_filter( 'views_edit-product', array( $this, 'product_ebayupdate_button' ),9999);
			}
		
			public  function plugin_activate() {
				if(!in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )){
				deactivate_plugins(basename(__FILE__)); // Deactivate ourself
                wp_die(__("WooCommerce Ebay UK Import requires WooCommerce installed and activated.",$this->textdomain).'<br /><a href="javascript:history.back(1);">'.__("<< Back",$this->textdomain).'</a>'); 
			  }
			}
			
			
			public  function register_my_custom_submenu() {
				add_menu_page(__('Ebay UK Import',$this->textdomain),__('Ebay UK Import',$this->textdomain), 'manage_options','ebayuk_import',array( &$this,'ebayuk_import_page_callback' ),null,58);
							
			}
			
			
			private function buildURLArray ($filterarray) {
			  $urlfilter='';
			  $i=0;
			  // Iterate through each filter in the array
			  foreach($filterarray as $itemfilter) {
				// Iterate through each key in the filter
				foreach ($itemfilter as $key =>$value) {
				  if(is_array($value)) {
					foreach($value as $j => $content) { // Index the key for each value
					  $urlfilter .= "&itemFilter($i).$key($j)=$content";
					}
				  }
				  else {
					if($value != "") {
					  $urlfilter .= "&itemFilter($i).$key=$value";
					}
				  }
				}
				$i++;
			  }
			  return "$urlfilter";
			}
			public function ebayuk_import_page_callback() {
				
				include_once( 'classes/ebayfinding.php' );
				$ebay = new ebay();
				//echo '<pre>'; print_r($_POST); echo '</pre>';
				$ebaycategories=$ebay->getCategories();
				
				$filterarray = array();$filters='';
				
				 if(isset($_POST['filters']) && sizeof($_POST['filters'])>0){
					
					 foreach($_POST['filters'] as $key=>$value){
						if(!empty($value)){
						$paramName=$paramValue='';
						 if($key=='MaxPrice' || $key=='MinPrice') {$paramName='Currency'; $paramValue='GBP';}
						 $filterarray[]= array(
						 'name' => $key,
						 'value' => $value,
						 'paramName' => $paramName,
						 'paramValue' => $paramValue);
						}
					 }
					 $filters = $this->buildURLArray($filterarray);
					//echo $filters;			
				 }
				
				$searchkey = (!empty($_POST['searchkey'])) ? $_POST['searchkey'] : '';
				$sort = (!empty($_POST['sort'])) ? $_POST['sort'] : '';
				$storename = (!empty($_POST['storename'])) ? $_POST['storename'] : '';
				$startpage = (!empty($_POST['startpage'])) ? $_POST['startpage'] : 1;
				$categoryid = (!empty($_POST['categoryid'])) ? $_POST['categoryid'] : '';
				$minprice = (!empty($_POST['filters']['MinPrice'])) ? $_POST['filters']['MinPrice'] : '';
				$maxprice = (!empty($_POST['filters']['MaxPrice'])) ? $_POST['filters']['MaxPrice'] : '';
				include( untrailingslashit( plugin_dir_path( __FILE__ ). '/templates/settings.php'));
				
				if(isset($_POST['searchkey'])){
				
				$safequery = urlencode($searchkey);
				$pre_page=30;
				//echo $_POST['searchby'].' '.$safequery.' '.$pre_page;
				$response = $ebay->findProduct($safequery, $pre_page,$sort,$storename,$startpage,$categoryid,$filters);
				$key = key((array)$response);
				$resp = $response->$key;
				$resp = $resp[0];
				//echo '<pre>'; print_r($resp); echo '</pre>';
				 
				
				
				if ($resp->ack[0] == "Success") {
						$args = array('taxonomy'     => 'product_cat', 'orderby'      => 'name',
					   'hide_empty'   => 0);
						$all_categories = get_categories( $args );
						include( untrailingslashit( plugin_dir_path( __FILE__ ). '/templates/results.php'));
				}else {
					echo "<h3>The request was not successful</h3><br>";
					echo $resp->errorMessage[0]->error[0]->message[0];
				}
		
				}
				
			 }
			 
			 public function importproduct_admin_action(){
			$ids=$_GET['itemId'];
			$ecategory=$_GET['category'];
			 //bof check unique
			 $args = array(
				'post_type' => 'product',
				'meta_key' => 'itemId',
				'meta_value' => $ids
			);

			$the_query = new WP_Query( $args );
			if($the_query->found_posts>0) die('Product with this itemId already exists in woocommerce');
			//eof check unique
			 
				 include_once( 'classes/ebayfinding.php' );
				 $ebay = new ebay();
				 $response = $ebay->get('GetSingleItem', $ids);
				//echo '<pre>'; print_r($response->Item); echo '</pre>';
				//die();
				if($response->Ack=="Success"){
					if(isset($response->Item->Variations->VariationSpecificsSet->NameValueList) && sizeof($response->Item->Variations->VariationSpecificsSet->NameValueList)>0){
						$new_taxonomies=array();
						$taxonomies=array();
						foreach($response->Item->Variations->VariationSpecificsSet->NameValueList as $namelist){
								$taxname=$this->get_taxname($namelist->Name);
								if(!taxonomy_exists('pa_'.$taxname)) $new_taxonomies[]=$namelist->Name;
								$taxonomies['pa_'.$taxname]=$namelist->Value;
							
						}
						$new_taxonomies=array_unique($new_taxonomies);
					    
						if(sizeof($new_taxonomies)>0) {$this->createTaxonomy($new_taxonomies);  
						echo '<script type="text/javascript">location.reload(true);</script>';
						die();
						}
					}
					//echo '<pre>'; print_r($taxonomies); echo '</pre>'; die();
					//global $wpdb;
					//$wpdb->query('START TRANSACTION');
					
					 $post = array(
					 'post_author' => 1, 
					 'post_content' => strip_tags($response->Item->Description),//preg_replace('#<script(.*?)>(.*?)</script>#is', '', $response->Item->Description), //
					 'post_status' => "publish",
					 'post_title' => $response->Item->Title,
					 'post_parent' => '',
					 'post_type' => "product",
					);
					$post_id = wp_insert_post( $post );
					
					//echo 'creating new product, id: '.$post_id.'<br>';
	
					if(isset($response->Item->Variations->Variation) && sizeof($response->Item->Variations->Variation)>0){
						//echo 'saving product variations <br>';
						wp_set_object_terms ($post_id,'variable','product_type');
						//bof added attributes to product
						foreach($taxonomies as $taxname=>$avail_attributes){
							//echo '<pre>'; print_r($avail_attributes); echo '</pre>';
							wp_set_object_terms($post_id, $avail_attributes, $taxname);
							$product_attributes[$taxname] = array (
								'name' => $taxname, 
								'value' => '', 
								'position' => 0,
								'is_visible' => 1,
								'is_variation' => 1,
								'is_taxonomy' => 1
							);
						}
						
						update_post_meta($post_id, '_product_attributes', $product_attributes);
						//eof added attributes to product
						//bof get variation pictures
						if(isset($response->Item->Variations->Pictures[0]->VariationSpecificPictureSet) && sizeof($response->Item->Variations->Pictures[0]->VariationSpecificPictureSet)>0){
							$pictaxname= $this->get_taxname($response->Item->Variations->Pictures[0]->VariationSpecificName);
							foreach($response->Item->Variations->Pictures[0]->VariationSpecificPictureSet as $pictures){
								$term=get_term_by('name', $pictures->VariationSpecificValue, 'pa_'.$pictaxname);
								if(isset($term->slug) && isset($pictures->PictureURL)){
								$pictaxterm=$term->slug;
								$gallery[$pictaxname][$pictaxterm]=$this->saveImage($pictures->PictureURL,null);
								}
							}
						}
						
						//eof get variation pictures
						$i=0;
						foreach($response->Item->Variations->Variation as $variation){
							$varprod = array(
							'post_title'=> 'Variation #' . $i . ' of 5 for prdct#'. $post_id,
							'post_name' => 'product-' . $post_id . '-variation-' . $i,
							'post_status' => 'publish',
							'post_parent' => $post_id,
							'post_type' => 'product_variation',
							'guid'=>home_url() . '/?product_variation=product-' . $post_id . '-variation-' . $i
							);
							$variation_id = wp_insert_post( $varprod );	
							if(isset($variation->SKU)) update_post_meta( $variation_id, '_sku', $variation->SKU);
							update_post_meta($variation_id, '_price', $variation->StartPrice->Value);
							update_post_meta($variation_id, '_regular_price', $variation->StartPrice->Value);
							update_post_meta( $variation_id, '_stock', $variation->Quantity );
							update_post_meta( $variation_id, '_visibility', 'visible' );
							
							 foreach($variation->VariationSpecifics->NameValueList as $namelist){
								$taxonomy= $this->get_taxname($namelist->Name);
								$term=get_term_by('name', $namelist->Value[0], 'pa_'.$taxonomy);
								
								
							update_post_meta( $variation_id,'_product_attributes',$product_attributes);	
							wp_insert_term( $term->name, 'pa_'.$taxonomy);
							wp_set_object_terms( $variation_id, $term->name, 'pa_'.$taxonomy);
							update_post_meta($variation_id, 'attribute_pa_'.$taxonomy, $term->slug);
							if(isset($gallery[$taxonomy][$term->slug])){
							 update_post_meta( $variation_id, '_product_image_gallery', implode(',',$gallery[$taxonomy][$term->slug]) );
							if(isset($gallery[$taxonomy][$term->slug][0])) set_post_thumbnail( $variation_id, $gallery[$taxonomy][$term->slug][0] );
							}
									
						 
						}
							 
						
							$i++;
					}
					//echo 'end of saving product variations<br>';
				} //end variations
					//die();
					if(!empty($response->Item->PictureURL) && sizeof($response->Item->PictureURL)>0){
						$this->saveImage($response->Item->PictureURL,$post_id);
					}
					update_post_meta( $post_id, '_regular_price', $response->Item->ConvertedCurrentPrice->Value );
					update_post_meta( $post_id, '_price', $response->Item->ConvertedCurrentPrice->Value );
					update_post_meta( $post_id, 'itemurl', $response->Item->ViewItemURLForNaturalSearch);
					update_post_meta( $post_id, 'EndTime', $response->Item->EndTime);
					update_post_meta( $post_id, 'itemId', $ids);
					update_post_meta( $post_id, '_visibility', 'visible' );
					update_post_meta( $post_id, 'BidCount', $response->Item->BidCount );
					update_post_meta( $post_id, '_sku', $response->Item->ItemID );
					if(!empty($ecategory)) wp_set_object_terms( $post_id, $ecategory, 'product_cat');
					//$wpdb->query('COMMIT');
				}
				//die('stop');
				 wp_redirect( get_bloginfo('url').'/wp-admin/post.php?post='.$post_id.'&action=edit' );
			 }
			 private function createTaxonomy($taxonomies){
				 global $wpdb;
				 //var_dump($taxonomies); die();
				 global $wp_taxonomies;
					//echo '<pre>'; print_r($wp_taxonomies); echo '</pre>'; 
				
				 foreach($taxonomies as $taxonomy){
					 $taxname=$this->get_taxname($taxonomy);
					 //echo 'pa_'.$taxname; die();
					 //var_dump(isset($wp_taxonomies['pa_'.$taxname]));
					 //echo $taxname.'<br>'; 
					 if(!isset($wp_taxonomies['pa_'.$taxname])){
						//echo 'saving new taxonomy '.$taxonomy.'...<br>';
						 $attribute = array(
						'attribute_label'   => ucfirst($taxonomy),
						'attribute_name'    => $taxname,
						'attribute_type'    => 'text',
						'attribute_orderby' => '',
						);

					$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );

					do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $attribute );
					$transient_name = 'wc_attribute_taxonomies';
					$attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies" );
					
					 
					set_transient( $transient_name, $attribute_taxonomies );
				
					
					}
				 }
				 return;
				// wp_redirect( get_bloginfo('url') . '/wp-admin/admin.php?action=importproduct&itemId='.$_GET['itemId'] );
			 }
			private function get_taxname($taxonomy){
				$taxname = substr($taxonomy, 0, 17);
				$taxname=str_replace(' ','_',strtolower($taxname));
				$taxname = preg_replace("/[^a-zA-Z0-9_]+/", "", $taxname);
				return $taxname;
			}
			  private function saveImage($urls,$post_id){
				 $gallery=array();
				 if(sizeof($urls)>0){
				foreach($urls as $key=> $imageurl){
				$current_url = explode('?', $imageurl);
				$image_url=$current_url[0];
				$upload_dir = wp_upload_dir(); // Set upload folder
				$image_data = file_get_contents($image_url); // Get image data
				$filename   = rand(1,999).$key.basename($image_url); // Create image file name

				if( wp_mkdir_p( $upload_dir['path'] ) ) {
					$file = $upload_dir['path'] . '/' . $filename;
				} else {
					$file = $upload_dir['basedir'] . '/' . $filename;
				}

				file_put_contents( $file, $image_data );

				$wp_filetype = wp_check_filetype( $filename, null );

				$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_title'     => sanitize_file_name( $filename ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);
				if(!empty($post_id))
				$attach_id = wp_insert_attachment( $attachment, $file, $post_id );
				else
				$attach_id = wp_insert_attachment( $attachment, $file );	
				$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				if($key==0 && !empty($post_id)) set_post_thumbnail( $post_id, $attach_id );
				$gallery[]= $attach_id;
				}
			  }
				if(!empty($post_id))
				update_post_meta( $post_id, '_product_image_gallery', implode(',',$gallery) );
				else 
				return $gallery;
			 
			 }
			 public function woocommerce_template_single_ebayuk(){
				global $product;
				$ebayurl=get_post_meta($product->id, 'itemurl', true); 
				if(!empty($ebayurl)){
					$plugin_url=untrailingslashit( plugin_dir_url( __FILE__ ));
					wp_enqueue_style( 'style-name', untrailingslashit( plugin_dir_url( __FILE__ )).'/assets/js/jquery.countdown/jquery.countdown.css' );
					wp_enqueue_script( 'script-name', untrailingslashit( plugin_dir_url( __FILE__ ) ). '/assets/js/jquery.countdown/jquery.plugin.js', array(), '1.0.0', true );
					wp_enqueue_script( 'script-name2', untrailingslashit( plugin_dir_url( __FILE__ )) . '/assets/js/jquery.countdown/jquery.countdown.js', array(), '1.0.0', true );
					

				 include( untrailingslashit( plugin_dir_path( __FILE__ ). '/templates/single_block.php'));
			 }
				 
			 }
			 public function product_new_column($columns){
				$columns['updateebayprice'] = __( 'Ebay Price'); 
				unset($columns['product_tag']);
				return $columns;
			 }
			

			public  function update_ebay_price( $column, $postid ) {
				if ( $column == 'updateebayprice' ) {
					$ebayurl=get_post_meta($postid, 'itemurl', true); 
					if(!empty($ebayurl)){
						echo '<a href="admin.php?action=updateebayprice&prodid='.$postid.'">Update</a>';
					}
				}
			}
				 public function updateebayprice_admin_action(){
					include_once( 'classes/ebayfinding.php' );
					$ebay = new ebay();
				if ( isset($_GET['prodid'])){
					$prodid=$_GET['prodid'];
					$itemId=get_post_meta($prodid, 'itemId', true); 
					$response = $ebay->get('GetSingleItem', $itemId);
					//echo $itemId;
					//print_r($response); die();
					if($response->Ack=="Success"){
						if(isset($response->Item->Variations->Variation) && sizeof($response->Item->Variations->Variation)>0){
								$this->update_variations_prices($response->Item->Variations->Variation,$prodid);
						}else{
							$price = $response->Item->ConvertedCurrentPrice->Value;
							update_post_meta( $prodid, '_regular_price', $price );
							update_post_meta( $prodid, '_price', $price );
						}
					}	
				}else{
					$args = array(
                        'post_type' => 'product',
                        'posts_per_page' => -1,
                        'meta_query' => array(
							array(
                                'key' => 'itemId',
                                'compare' => '>=',
                                'value' => '0'
                            ),
                        ),
						
                    );
					$prodquery = new WP_Query($args);
					$products=array();
					while ($prodquery->have_posts()) : $prodquery->the_post();
					$itemId = get_post_meta(get_the_ID(), 'itemId', true);
					 $products[get_the_ID()]=$itemId;
					 $products1[$itemId]=get_the_ID();
					endwhile;
					wp_reset_query();
					
					$i=0;
					do {
					$arr=array_slice($products,$i,20);
					$itemIds=implode(',',$arr);
					$response = $ebay->get('GetMultipleItems', $itemIds);
					if($response->Ack=="Success"){
						foreach($response->Item as $item){
							$itemid=$item->ItemID;
							$prodid=$products1[$itemid];
							//echo 'prodid: '.$prodid.'<br>';
							if(isset($item->Variations->Variation) && sizeof($item->Variations->Variation)>0){
								$this->update_variations_prices($item->Variations->Variation,$prodid);
							}else{
						
							$price = $item->ConvertedCurrentPrice->Value;
							update_post_meta( $prodid, '_regular_price', $price );
							update_post_meta( $prodid, '_price', $price );
							}
						}	
					}	
					$i+=20;
					} while (sizeof($arr) > 0);
					
				}
				//die();
				wp_redirect( get_bloginfo('url').'/wp-admin/edit.php?post_type=product' );
			 }
			private function update_variations_prices($variations,$prodid){
				foreach($variations as $variation){
					$args = array( 'post_type' => 'product_variation', 'posts_per_page' => 1, 'post_parent'=>$prodid );
					$args['meta_query']=array('relation' => 'AND');
					 foreach($variation->VariationSpecifics->NameValueList as $namelist){
						$taxonomy= $this->get_taxname($namelist->Name);
						$term=get_term_by('name', $namelist->Value[0], 'pa_'.$taxonomy);
						if(isset($term->slug)){
						$args['meta_query'][]=array(
							 'key' =>' attribute_pa_'.$taxonomy,
							 'compare' => '==',
							 'value' => $term->slug
						);
						}
					}
					if(sizeof($args['meta_query'])>1){
					$loop = new WP_Query( $args );
					while ( $loop->have_posts() ) {
						$loop->the_post(); 
						$var_id=get_the_ID();
						update_post_meta($var_id, '_price', $variation->StartPrice->Value);
						update_post_meta($var_id, '_regular_price', $variation->StartPrice->Value);
					}
					wp_reset_query(); 
					}
				}
			}
		
			public function product_ebayupdate_button($views ){
				$views['my-button'] = '<a href="admin.php?action=updateebayprice"><button id="update-from-provider" type="button"  title="Update from Provider" style="margin:5px">Update Prices from Ebay</button></a>';
				return $views;
			}
		}
		
		
		
		// instantiate our plugin class and add it to the set of globals
		$GLOBALS['wc_ebayukimport'] = new WC_EbayUKImport();
		
	}
