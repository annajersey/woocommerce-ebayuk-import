<?php
/**
 * The template for customer levels.
 *
 */
 ?>
 <style>
 .small-input{
	 width:70px;
}
.form-table th{width:115px;}
 </style>

  
    <div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        <h2><?php _e('Ebay Import',$this->textdomain); ?></h2>
		<form method="POST" id="ebay_search" >
		<table class="form-table" style="width:50%; float:left;clear:none;">
		<tr>
			<th><label for="searchkey">Keyword</label></th>
			<td><input type="text" name="searchkey" value="<?php echo stripslashes($searchkey); ?>"></td>
		</tr>
		
		
		<tr><th><label for="sort">Sort By</label></th>
		<td><select name="sort">
			<option <?php if($sort=='BestMatch') echo 'selected="selected"'; ?> value="BestMatch">Best Match</option>
			<option <?php if($sort=='CurrentPriceHighest') echo 'selected="selected"'; ?> value="CurrentPriceHighest">Current Price (Highest)</option>
			<option <?php if($sort=='PricePlusShippingHighest') echo 'selected="selected"'; ?> value="PricePlusShippingHighest">Price Plus Shipping (Highest)</option>
			<option <?php if($sort=='PricePlusShippingLowest') echo 'selected="selected"'; ?> value="PricePlusShippingLowest">Price Plus Shipping (Lowest)</option>
			<option <?php if($sort=='StartTimeNewest') echo 'selected="selected"'; ?> value="StartTimeNewest">Start Time Newest</option>
			<option <?php if($sort=='EndTimeSoonest') echo 'selected="selected"'; ?> value="EndTimeSoonest">End Time Soonest</option>
		</select>
		</td>
		</tr>
		<tr>
		<th><label for="startpage" >Start Page</label></th>
		<td><input type="text" id="startpage" name="startpage" value="<?php echo stripslashes($startpage); ?>"></td>
		</tr>
		<tr>
			<th><label for="searchkey">Category</label></th>
			<td><select name="categoryid">
			<option value="">All</option>
			<?php  foreach($ebaycategories->CategoryArray->Category as $cat){ if($cat->CategoryID>0){?>
						<option <?php if($categoryid==$cat->CategoryID) echo 'selected="selected"'; ?> value="<?php echo  $cat->CategoryID; ?>"><?php echo  $cat->CategoryName; ?></option>
			<?php }}
			?>	 
			</select>
			</td>
		</tr>
		<tr id="storename" >
		<th><label for="storename">Store Name</label></th>
		<td><input type="text" name="storename" value="<?php echo stripslashes ($storename); ?>"></td>
		</tr>
		</table>
		<table class="form-table" style="width:50%; float:right;clear:none;">
		
		<tr>
		<th>Priced</th>
		<td>From&nbsp;&nbsp;<input class="small-input" type="text" name="filters[MinPrice]" value="<?php echo htmlspecialchars($minprice);?>">&nbsp;£&nbsp;&nbsp;&nbsp;
		To&nbsp;&nbsp;<input class="small-input" type="text" name="filters[MaxPrice]" value="<?php echo htmlspecialchars($maxprice);?>">&nbsp;£</td>
		</tr>
		<tr>
			<th><label>Buying formats</label></th>
			<td><input type="checkbox" value="Auction" name="filters[ListingType][]" <?php if(!empty($_POST['filters']['ListingType']) && in_array("Auction",$_POST['filters']['ListingType'])) {echo 'checked="checked"';}?>>Auction<br>
			<input type="checkbox" value="AuctionWithBIN" name="filters[ListingType][]" <?php if( !empty($_POST['filters']['ListingType']) && in_array("AuctionWithBIN",$_POST['filters']['ListingType'])) {echo 'checked="checked"';}?>>Buy it now<br>
			<input type="checkbox" value="Classified" name="filters[ListingType][]" <?php  if(!empty($_POST['filters']['ListingType']) && in_array("Classified",$_POST['filters']['ListingType'])) {echo 'checked="checked"';}?>>Classified Ads<br>
			<input type="checkbox" value="FixedPrice" name="filters[ListingType][]" <?php  if(!empty($_POST['filters']['ListingType']) && in_array("FixedPrice",$_POST['filters']['ListingType'])) {echo 'checked="checked"';}?>>FixedPrice
			</td>
		</tr>
		<tr>
		<th><label>Condition</label></th>
		<td>
			<input type="checkbox" value="New" name="filters[Condition][]" <?php  if(!empty($_POST['filters']['Condition']) && in_array("New",$_POST['filters']['Condition'])) {echo 'checked="checked"';}?>>New<br>
			<input type="checkbox" value="Used" name="filters[Condition][]" <?php if(!empty($_POST['filters']['Condition']) && in_array("Used",$_POST['filters']['Condition'])) {echo 'checked="checked"';}?>>Used
		</td>
		</tr>
		</table>
		<div style="clear:both"></div>
		<p class="submit">
		<input class="button button-primary" type="submit" value="Search" >
		</p>
		</form>

    </div>
