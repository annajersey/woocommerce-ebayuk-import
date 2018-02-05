<?php
/**
 * The template for customer levels.
 *
 */
 ?>
 <style>
 #ebay_results {border-collapse:collapse;clear:both; width:100%;}
 .econt{}
 #ebay_results img{height:100px;width:100px;}
  #ebay_results a{color: #999; text-decoration:none; font-size:13px;}
  #ebay_results a:hover{text-decoration:none;}
  #ebay_results td{border:1px solid #e2e2e2; padding:5px;}
  #results_num{font-weight:bold; margin:0px 0 10px 0; float:left;}
  #page_num{font-weight:bold; margin:0px 0 10px 0; float:right;}
  #results{padding:10px 50px; background: #fff;max-width:880px;width:100%; overflow:hidden;}
  #ebay_results .odd{background:#f8f8f8;}
  #ebay_results .title{font-size:15px; padding:20px;text-align:left;}
  #ebay_results td{text-align:center;}
  #ebay_results .price{white-space:nowrap}
  #ebay_results button{cursor: pointer}
  #ebay_results  th{border:1px solid #e2e2e2; padding:5px;background:#f8f8f8;}
  .epag {font-weight:bold; cursor:pointer; display:inline-block; font-size:13px;}
  .epag span{font-size:15px;}
  .paging{float:right; clear:both; margin:10px 0;}
 </style>
 <?php  //echo '<pre>'; print_r($resp->paginationOutput[0]); echo '</pre>'; 
		$current_page=$resp->paginationOutput[0]->pageNumber[0];
		$total_pages=$resp->paginationOutput[0]->totalPages[0];
		$total_items=$resp->paginationOutput[0]->totalEntries[0];
		?>
 
    <div class="wrap" id="results">
        <div class="paging"><?php if($current_page>1){ ?><div   class="epag" onclick="ebayPrev()"><span>&laquo; </span>Previous</div> | <?php } ?><?php if($current_page<100){ ?><div  class="epag" onclick="ebayNext()">Next <span>&raquo;</span></div><?php } ?></div>
        
		
		<div style="clear:both;">
		<div id="results_num"><?php echo number_format($total_items,0,'.',','); ?> <?php _e('results found',$this->textdomain); ?></div>
		<div id="page_num"><?php _e('Page',$this->textdomain); ?> <?php echo $current_page; ?>/<?php echo number_format($total_pages,0,'.',','); ?> </div>
		</div>
		<?php 
		$i=0;
		if(isset($resp->searchResult[0]->item) && sizeof($resp->searchResult[0]->item)>0){
			$results='';
		foreach($resp->searchResult[0]->item  as $item) {
			$i++;
			$pic   = isset($item->galleryURL[0]) ? '<img src="'.$item->galleryURL[0].'" />' : wc_placeholder_img();
			$link  = $item->viewItemURL[0];
			$title = $item->title[0];
			$price=$item->sellingStatus[0]->convertedCurrentPrice[0]->__value__;
			
			
				$categories='<select id="c'.$item->itemId[0].'">';
				$categories.='<option value="0">Select</option>';
				if(sizeof($all_categories)>0){
					foreach($all_categories as $cat){
						
						$categories.='<option value="'.$cat->slug.'">'.$cat->name.'</option>';
					}	
				}
				$categories.='</select>';
			
		    $save = sprintf('<a class="save" target="_blank" onclick="window.open(\'?action=%s&itemId=%s&category=\'+jQuery(\'#c'.$item->itemId[0].'\').val())"><button>'.__("Import",$this->textdomain).'</button></a>','importproduct',$item->itemId[0]);
			if($i%2==0) $class="odd"; 
			else $class="";
			$results .= "<tr class='".$class."'><td>".$pic."</td><td valign='top' class='title'>$title<br><br><a target='_blank' href=\"$link\">View Item</a></td><td class='price'>".$price." £</td><td>". $save."</td><td>".$categories."</td></tr>";
		  }
		} 
		?>
		<?php if($resp->paginationOutput[0]->totalEntries[0]>0): ?>
		<table id="ebay_results" >
		<tr><th>Image</th><th>Details</th><th>Price</th><th>Import</th><th>To category</th></tr>

    <?php echo $results;?>
  
</table>

<?php endif; ?>
 <div class="paging"><?php if($current_page>1){ ?><div   class="epag" onclick="ebayPrev()"><span>&laquo; </span>Previous</div> | <?php } ?><?php if($current_page<100){ ?><div  class="epag" onclick="ebayNext()">Next <span>&raquo;</span></div><?php } ?></div>
    </div>
	
<script>
function ebayNext(){
	jQuery('#startpage').val(<?php echo ($current_page+1); ?>);
	jQuery('#ebay_search').submit();
}	
function ebayPrev(){
	jQuery('#startpage').val(<?php echo ($current_page-1); ?>);
	jQuery('#ebay_search').submit();
}	
</script>