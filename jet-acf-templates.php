<?php
/**
 * @package Triangle Web Tech Tools
 * @version 1.6
 */
/*
Plugin Name: ACF Templating
Plugin URI: http://trianglewebtech.com/wordpress/plugins/jet-acf-templates
Description: Enables simple content layout for ACF field contents.
Author: Jonathan Tweedy
Version: 1.0
Author URI: http://jonathantweedy.com
*/

/*
[jet-acf-template post-id="11"]<div>{:test_1:}<br />{:test_2:}</div>[/jet-acf-template]
<hr />
[jet-acf-template post-type="sample"]<div>{:test_1:}<br />{:test_2:}</div>[/jet-acf-template]
[jet-acf-template post-type="news"]
{if: field:test_1 op:eq args:"Test 1":}
{:test_1:}
{/if: field:test_1 op:eq args:"Test 1":}
{rf:test_2:}
{:r_1:} | {:r_2:}
{/rf:test_2:}
[/jet-acf-template]
*/

ini_set("display_errors", 1);

remove_filter("the_content", "wptexturize");
remove_filter("comment_text", "wptexturize");
remove_filter("the_excerpt", "wptexturize");
//remove_filter( 'the_content', 'wpautop' );
//add_filter( 'the_content', 'wpautop' , 99);
function jetacf_content_filter($content) {
	//// If jet-acf-template is not found in the content
    if ($content!=str_replace("jet-acf-template","",$content)) {
    	$content = str_replace("\n","",$content);
		$content = wpautop($content);
    }
    return $content;
}
//add_filter( 'the_content', 'jetacf_content_filter', 20 );


class jetAcfTemplate {
    public static function checkCondition($value, $op, $argstr) {
		$r = false;
		if (!is_array($value)) {
			$value = trim($value);			
		}
        $argsjson = "{\"args\":".$argstr."}";
        $args = json_decode($argsjson);
        $comp = $args->args;
		if (!is_array($comp)) { $comp = trim($comp); }
        if ($op=="eq") {
            if (!(is_array($comp) || $value != $comp)) {
                $r = true;
            }
        }
        if ($op=="neq") {
            if (is_array($comp) || $value != $comp) {
                $r = true;
            }
        }
        if ($op=="in") {
            if (is_array($comp) && in_array($value,$comp)) {
                $r = true;
            }
        }
        if ($op=="has") {
            if (is_array($value)) {
				foreach($value as $v => $val) {
					foreach($val as $k=>$kv) {
						if ($kv==$comp) {
							$r = true;
						}
					}
				}
            }
        }
//print_r($value); print "<hr />";
//print "[$value] $op [$comp] :: $r <br />";	
        return $r;
    }
}

function jet_acf_template( $atts, $templatecontent ){

	//// Establish pagination
	$jetp = isset($_GET['jetp']) ? $_GET['jetp'] : 0;
	if (!is_numeric($jetp)) { $jetp = 0; }
	$jetp = floor($jetp);
	if ($jetp<0) { $jetp = 0; }
//	print $jetp; die;
	
	global $use_wpautop;
	$_SESSION['use_wpautop'] = false;	
	
	$templatecontent = str_replace("\n","",$templatecontent);

	$content = "";
	
	$queryParams = array();	
	
	$post_id = false;
	if ($_GET['post_id']) {
		$post_id = $_GET['post_id'];
	}
	if (!!$post_id) {
		$queryParams['p'] = $post_id;
	}
	
	if (isset($atts['post-type'])) {
		$queryParams['post_type'] = explode(",",$atts['post-type']);
	} else {
		$queryParams['post_type'] = "post";
	}
	$queryParams['posts_per_page'] = isset($atts['posts_per_page']) ? $atts['posts_per_page']+1 : -1;
	$queryParams['offset'] = isset($atts['offset']) ? $atts['offset'] : $atts['posts_per_page']*$jetp;
	$paginate = isset($atts['paginate']) ? 1 : 0;
	if (isset($atts['category'])) {
		$queryParams['category_name'] = $atts['category'];
	}
	$queryParams['tax_query'] = array();
	$nonTaxParams = array("post-type","category","offset","paginate","orderby");
	if (!empty($atts)) {
		foreach($atts as $tax=>$values) {
			if ( !in_array($tax,$nonTaxParams) && taxonomy_exists($tax) ) {
				$values = is_array($values) ? $values : explode(",",$values);
				$queryParams['tax_query'][] = array(
					'taxonomy' => $tax,
					'field'    => 'slug',
					'terms'    => $values,
				);				
			}
		}
	}
	
	$atts['orderby'] = trim($atts['orderby']);
	if (strtoupper($atts['order']!="DESC")) {
		$atts['order'] = "ASC";
	}
	if (in_array($atts['orderby'], ["title"])) {
		$queryParams["orderby"] = $atts['orderby'];
		$queryParams["order"] = $atts['order'];
	} else if ($atts['orderby']!="") {
		$queryParams["meta_key"] = $atts['orderby'];
		$queryParams["orderby"] = "meta_value";
		$queryParams["order"] = $atts['order'];
//		$queryParams["orderby"] = array("meta_value" => $atts['order']);
	}

	$query = new WP_Query( $queryParams );
	
//// {:photos.0.photo.url:}
//	photos[0].photo
//					.url
//					.sizes.thumbnail, etc
	
	if (isset($query)) {

		//// Build up array of multi-tier values (like multiple photos)
		//// Accommodated a pattern like this... {:photos[0].photo:}
		$multiples = [];
		$multiplespattern = "/\{:([a-zA-Z0-9-_]+?)\.([0-9a-zA-Z-_\.]+):\}/si";
//		$multiplespattern = "/\{:([a-zA-Z0-9-_]*?)\[([0-9]+)\]\.(.*?):\}/si";
		$templatecontent = html_entity_decode($templatecontent);
		if (preg_match_all($multiplespattern, $templatecontent, $mms)) {
			for($m=0;$m<count($mms[0]);$m++) {
				$orig = $mms[0][$m];
				$var = $mms[1][$m];
				$idx = $mms[2][$m];
//				print $var . "[" . $idx . "]." . $prop . "<hr />";
				if (!isset($multiples[$var])) { $multiples[$var] = []; }
				if (!isset($multiples[$var][$idx])) { $multiples[$var][$idx] = $prop; }
			}
		};
	
		//// Fetch all fields that match the generic {:field:} pattern
		$pattern = "/\{[:|\{]([a-zA-Z0-9-_]*?)[:|\}]\/?\}/s";
		$fields = array();
		if (preg_match_all($pattern, $templatecontent, $matches)) {
			if (!empty($matches)) {
				foreach($matches[1] as $match) {
					$fields[] = $match;
				}
			}
		}
		
		
    	$repeaterfieldpattern = "/\{rf:(.*?):(.*?)\}(.*?)\{\/rf:\g{-3}:\g{-2}\}/s";
    	$conditionalpattern = "/\{if: field:(.*?) op:(.*?) args?:(.*?):\}(.*?)\{\/if.*?}/s";
		//// Simple loop through all queried posts
	    $fieldvals = array();
		
		$pNum = 0;
		
		while($query->have_posts()) : $query->the_post();

			if ($atts['posts_per_page']<1 || $pNum < $atts['posts_per_page']) {
				$pNum++;
				$post_id = get_the_id();
				$postFields = array();
				$postFields['post_id'] = $post_id;
				$postFields['post_title'] = get_the_title();
				$postFields['post_content'] = wpautop( get_the_content() );
				$postFields['post_permalink'] = get_permalink();
				$postFields['post_excerpt'] = get_the_excerpt();
				$postFields['post_date'] = get_the_date();
				if (has_post_thumbnail( $post->ID )) {
					$postFields['post_thumbnail'] = get_the_post_thumbnail_url();
					$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
					$postFields['post_image'] = $image[0];
				}
				
				$allfields = get_fields($post_id);
				if (!empty($allfields)) {
					foreach($allfields as $field=>$value) {
						$fieldvals[$post_id."__".$field] = $value;
					}
				}
//				print_r($allfields); die;
				$postcontent = $templatecontent;		
				//// Inventory simple fields
				foreach($fields as $field) {
					$value = get_field($field);
					$fieldvals[$post_id."__".$field] = $value;
				}
				
				
				//// Match any repeater fields
				if (preg_match_all($repeaterfieldpattern, $templatecontent, $repeatermatches)) {
					for ($ri=0;$ri<count($repeatermatches[0]);$ri++) {
						$repeateroutput = "";
						$repeaterfield = $repeatermatches[1][$ri];
						$repeaterdelimiter = $repeatermatches[2][$ri];  
						$children = get_field($repeaterfield);
						$childtemplate = $repeatermatches[3][$ri];
						if (is_array($children) && count($children) > 0 ) {
							$useDelimiter = false;
							foreach($children as $child) {
								$repetition = $childtemplate;
								foreach($child as $pat=>$val) {
									$fieldvals[$post_id."__".$repeaterfield."__".$pat][] = $val;
									$repetition = str_replace("{:".$pat.":}",$val,$repetition);
								}
								if ($useDelimiter) { 
									$repeateroutput .= $repeaterdelimiter;
								}
								$repeateroutput .= $repetition;
								$useDelimiter = true;
							}

//							$repeateroutput .= $repetition;
						}
						$postcontent = str_replace($repeatermatches[0][$ri],$repeateroutput,$postcontent);
					}
				}
				//// Match and process conditional fields
				while (preg_match_all($conditionalpattern, $postcontent, $conditionalmatches)) {
					for ($ci=0;$ci<count($conditionalmatches[0]);$ci++) {
						$fieldname = $post_id . "__" . $conditionalmatches[1][$ci];
						$fvalue = $fieldvals[$fieldname];
						if (isset($postFields[$conditionalmatches[1][$ci]])) {
							$fvalue = $postFields[$conditionalmatches[1][$ci]];
						}
						$op = $conditionalmatches[2][$ci];
						$arg = $conditionalmatches[3][$ci];
						if( jetAcfTemplate::checkCondition($fvalue, $op, $arg) ) {
							$postcontent = str_replace($conditionalmatches[0][$ci], $conditionalmatches[4][$ci], $postcontent);
						} else {
							$postcontent = str_replace($conditionalmatches[0][$ci], "", $postcontent);
						}
					}
				}
				
				if (!empty($multiples)) {
					foreach($multiples as $m => $multiple) {
						$master = get_field($m);
						foreach($multiple as $is => $indexstring) {
							$value = $master;
							$ixs = explode(".",$is);
							foreach($ixs as $ix) {
								$value = $value[$ix];
							}
							$original = "".$m.".".$is."";
							$postcontent = str_replace("{:".$original.":}",$value,$postcontent);
						}
					}
				}
				
				foreach($fields as $field) {
					if (isset($postFields[$field])) {
						$value = $postFields[$field];
					} else {
						$value = get_field($field);
					}
					$postcontent = str_replace("{{".$field."}}",$value,$postcontent);
					$postcontent = str_replace("{:".$field.":}",$value,$postcontent);
	//				$postcontent = str_replace("{:post_content:}",get_the_content(),$postcontent);
	//				$postcontent = str_replace("{:post_title:}",get_the_title(),$postcontent);
				}
				$content .= $postcontent;
			}
		endwhile;
		
//		print "paginate: {$paginate} | offset: {$queryParams['offset']}";
//		print "<hr />";
//		print $pNum . " | " . $query->post_count;
//		print "<hr />";
		$showPagination = false;
		if ($paginate) {
			
			
			$prevLink = "<a style='float:left;' href='?jetp=".($jetp-1)."'><< Prev</a>";
			$nextLink = "<a style='float:right;' href='?jetp=".($jetp+1)."'>Next >></a>";
			
			$pcontent = "<div style='margin:10px;'>";
			if ($jetp > 0) {
				$pcontent .= $prevLink;
				$showPagination = true;
			}
			if ($pNum < $query->post_count) {
				$pcontent .= $nextLink;
				$showPagination = true;
			}
			$pcontent .= "&nbsp;</div>";
			if ($showPagination) {
				$content = $pcontent . $content;
			}
		}
		
		wp_reset_postdata();
	}
	return $content;
}
add_shortcode( 'jet-acf-template', 'jet_acf_template' );



?>
