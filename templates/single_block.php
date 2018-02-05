
<img src="<?php echo $plugin_url.'/assets/images/ebay.png'; ?>" width="100" />

<div style="border:1px solid #fafafa; border-radius:5px; padding:20px; background:#f5f5f5">
<?php
//date_default_timezone_set('Europe/London');


//date_timezone_set('Europe/London');


$enddate=get_post_meta($product->id, 'EndTime', true); 
$bidcount=get_post_meta($product->id, 'BidCount', true); 
$helper = explode('T',$enddate);
$date=$helper[0];
$d1=explode('-',$date);
$timehelper=explode('.',$helper[1]);
$time=$timehelper[0];
$t1=explode(':',$time);
?>

<?php // echo $date.' '.$time; ?>
<div id="defaultCountdown"></div>
<br />


<a href="<?php echo $ebayurl; ?>" target='_blank'><button class="single_add_to_cart_button button alt" type="button">View Auction</button></a><!--<div style="text-align:right;float:right;line-height:46px;font-size:13px;">Bid Count: <?php echo $bidcount; ?></div>-->
</div>
<script>
var newYear = new Date(); 
newYear = new Date(<?php echo $d1[0]; ?>, <?php echo ($d1[1]-1); ?>, <?php echo $d1[2]; ?>, <?php echo $t1[0]; ?>, <?php echo $t1[1]; ?>, <?php echo $t1[2]; ?> ); 
jQuery(document).ready(function () {
jQuery('#defaultCountdown').countdown({until: newYear,timezone: +120});
});
</script>