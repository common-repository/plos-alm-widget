<?php
/*
Plugin Name: PLOS ALM Widget
Plugin URI: http://article-level-metrics.plos.org/wpwidget
Description: PLOS ALM Widget will display PLoS article-level metrics (ALMs) based on the ALM 2.0 API. It can accommodate the display of ALMs for a single or multiple articles. 
Author: Public Library of Science
Version: 1.0
Author URI: http://www.plos.org
*/


/*
 * Admin page to manage ALM articles for the widget
 */
// add the admin options page
add_action('admin_menu', 'alm_widget_admin_add_page');
function alm_widget_admin_add_page() {
    $alm_widget_admin_page = add_theme_page('ALM widget settings', 'ALM Widget settings', 'manage_options', 'alm_widget', 'alm_widget_options_page');
    add_action('admin_print_scripts-' . $alm_widget_admin_page, 'alm_widget_admin_scripts');

}

function alm_widget_admin_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('utils');
}

function alm_widget_options_page() {
	$loading_img = plugins_url( 'loading.gif', __FILE__ );
?>
<div class="wrap">
<?php screen_icon(); ?>
    <h2>ALM Widget settings</h2>

<form class="alm_widget_settings" action="options.php" method="post">
<?php settings_fields('alm_widget_options'); ?>
<input name="Submit" class="submit_alm" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
<div class="errorform"></div>
<?php do_settings_sections('alm_widget_apikey'); ?>
<?php 
$alm_widget_opts = get_option('alm_widget_options');
if (isset($alm_widget_opts['text_apikey'])) {
	do_settings_sections('alm_widget_articles');
}
?>
<div class="errorform"></div>
<input name="Submit" class="submit_alm" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
</form>

</div>
<script language="JavaScript">
jQuery(document).ready(function() {
<?php
if (isset($alm_widget_opts['text_apikey'])) {
	echo "\tvar plos_api_key = '".$alm_widget_opts['text_apikey']."';\n";
}
?>
	var ok_submit = true;
	var check = '';
	var loading_img = "<img src='<?php echo $loading_img; ?>' height='16' width='16' />";

jQuery('.wrap').on('keypress', '.doi', function(){
		var input_id = jQuery(this).attr('id');
//		var jQuery(this).val();
	check = input_id;
	ok_submit = false;
});
	jQuery('.wrap').on('blur', '.doi', function(){
		var doi = jQuery(this).val();
		var input_id = jQuery(this).attr('id');

		if (doi.length > 0 ) {
			ok_submit = validate_doi(doi, input_id, loading_img);
		} else {
			jQuery('#'+input_id+' ~ .error').html("");
			jQuery('.errorform').html('')
	        ok_submit = true;
		  	enable_save();
		}
	});

	jQuery('.wrap').on('submit', '.alm_widget_settings', function(){
		var check = jQuery('.doi');
		jQuery.each(check, function() {
			var check_doi = jQuery(this).val();
			var check_input_id = jQuery(this).attr('id');
			if (check_doi.length > 0 ) {
				ok_submit = validate_doi(check_doi, check_input_id);
			}
		});
//		console.log(ok_submit);
//		console.log(check);
		return ok_submit;
	});


});

function enable_save() {
	jQuery('.errorform').html('');
	jQuery('.submit_alm').attr('disabled', false);
}
function disable_save() {
	jQuery('.errorform').html('There is invalid data on the form, please correct it before saving.')
	jQuery('.submit_alm').attr('disabled', true);
}
function validate_doi(doi,input_id,loading_img) {
	var ok_submit = '';
	jQuery('.errorform').html('Please wait... ');
	var success = false;
	jQuery('#'+input_id+' ~ .error').html(loading_img);
	jQuery.ajax({
	  url: 'http://alm.plos.org/articles/'+doi+'.json',
	  dataType: 'jsonp',
	  data: {api_key: 'plos_api_test'},
	  success: function(data) {
  		jQuery('.errorform').html('');
		jQuery('#'+input_id+' ~ .error').html(" valid DOI").css('color','#090');
	  	if (data=="") { console.log('no data');}
	  	enable_save();
        ok_submit = true;
	  	success = true;
	  	check = '';
	  },
      error: function(jqXHR,error, errorThrown) {  
       if(jqXHR.status&&jqXHR.status==400){
            alert(jqXHR.responseText); 
       }else{
           alert("Something went wrong");
       }
         }

		});
	setTimeout(function() {
	    if (!success) {
			jQuery('#'+input_id+' ~ .error').html("Invalid DOI entered.").css('color','#900');
	        // Handle error accordingly
	        ok_submit = false;
	        disable_save();
	    }
	}, 1000);
	return ok_submit;
}
</script>

<?php
}

add_action('admin_init', 'alm_widget_admin_init');

function alm_widget_admin_init(){
register_setting( 'alm_widget_options', 'alm_widget_options', 'alm_widget_options_validate' );

//API Key
add_settings_section('alm_widget_apikey', 'ALM API Key', 'apikey_section_text', 'alm_widget_apikey');
add_settings_field('alm_text_apikey', 'Enter your ALM API Key', 'alm_settings_text', 'alm_widget_apikey', 'alm_widget_apikey', array('id'=>'text_apikey', 'name'=>'alm_widget_options[text_apikey]', 'description' => 'Enter your API key. If you do not have a PLOS API Key, please register for a key at <a href="http://api.plos.org/" target="_blank">http://api.plos.org/</a>'));

//Articles to display
add_settings_section('alm_widget_articles', 'ALM Articles', 'apikey_section_text', 'alm_widget_articles');

add_settings_field('alm_text_title', 'Enter the title for the widget', 'alm_settings_text', 'alm_widget_articles', 'alm_widget_articles', array('id'=>'text_title', 'name'=>'alm_widget_options[text_title]', 'description' => 'Enter the title with maximum 100 characters that will be displayed at the top of the widget.', 'maxlength'=>100));

add_settings_field('alm_text_article1', 'DOI for the first article', 'alm_settings_text', 'alm_widget_articles', 'alm_widget_articles', array('id'=>'text_article1', 'name'=>'alm_widget_options[text_article1]', 'description' => 'Enter the DOI of the first article.', 'class'=>'doi'));

add_settings_field('alm_text_article2', 'DOI for the second article', 'alm_settings_text', 'alm_widget_articles', 'alm_widget_articles', array('id'=>'text_article2', 'name'=>'alm_widget_options[text_article2]', 'description' => 'Enter the DOI of the second article.', 'class'=>'doi'));

add_settings_field('alm_text_article3', 'DOI for the third article', 'alm_settings_text', 'alm_widget_articles', 'alm_widget_articles', array('id'=>'text_article3', 'name'=>'alm_widget_options[text_article3]', 'description' => 'Enter the DOI of the third article.', 'class'=>'doi'));

}

function apikey_section_text() {
//    echo '<p>Enter the information for the banner buttons on the frontpage.</p>';
}

function alm_settings_text($args) {
    $options = get_option('alm_widget_options');
    if ($options['text_title'] == '') {$options['text_title'] = 'PLOS Articles Published';}
    if (isset($args['maxlength'])) {
    	$maxlength = 'maxlength="'.$args['maxlength'].'"';
    } else {
	$maxlength = '';
	}
    if (isset($args['class'])) {
    	$class = $args['class'];
	} else {
		$class = '';
	}
    echo "<input class='".$class."' id='".$args['id']."' name='".$args['name']."' size='40' ".$maxlength." type='text' value='{$options[$args['id']]}' /><span class='error'></span><br /><p><small>".$args['description']."</small></p>";
}

function alm_widget_options_validate($input) {
return $input;
}

function alm_admin_warnings() {
	global $alm_api_key;
	if ( !get_option('alm_widget_options') && !$alm_api_key ) {
		function alm_warning() {
			echo "
			<div id='alm-warning' class='updated fade'><p><strong>".__('The Article Level Metrics Widget is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your ALM API key</a> for it to work.'), "themes.php?page=alm_widget")."</p></div>
			";
		}
		add_action('admin_notices', 'alm_warning');
		return;
	}
}
alm_admin_warnings();


class alm_stats_widget extends WP_Widget {
	public function __construct() {
	//Constructor
		parent::__construct(
	 		'alm_widget', // Base ID
			'Article Level Metrics', // Name
			array( 'description' => __( 'Widget to display ALM for articles published', 'text_domain' ), ) // Args
		);
	}


	public function widget($args, $instance) {
	    $options = get_option('alm_widget_options');
	    $articles = array(
	    	array('article' => $options['text_article1'], 'authors' => $options['text_author1'], 'journal' => $options['text_journal1']),
	    	array('article' => $options['text_article2'], 'authors' => $options['text_author2'], 'journal' => $options['text_journal2']),
	    	array('article' => $options['text_article3'], 'authors' => $options['text_author3'], 'journal' => $options['text_journal3'])
	    	);

	// prints the widget
		extract($args, EXTR_SKIP);
		$title = $options['text_title'];
		$tag = $instance['selecttag'];
		$number = sizeof($articles);
    ?>
    <div class="alm_widget_container">
        	<?php 
        	if ($articles[0]['article']) { //check if we have at least 1 article to display the title
        	?>
  		<style type="text/css">
  		.alm_widget_title {background: none repeat scroll 0 0 #20366F !important; border-radius: 5px 6px 0 0; color: #FFFFFF !important; font-size: 110% !important; line-height: 130%; margin-bottom: 0px; padding: 6px 3px; text-align: center; text-transform: none !important; border: medium none; font-weight: bold;}
  		.alm_data { background: #baccd8; color: #000; padding: 2px 5px 4px; font-size: 11px; min-width: 130px; max-width: 49%; display: inline-block; vertical-align: top; margin: 2px 0 5px; }
  		.alm_title { background: #3e6fb2; color: #fff; text-align: center; padding: 2px 0; font-weight: bold; margin: -2px -5px 2px;}
  		.alm_article_title a {font-weight: bold; color: #000 !important;}
  		.alm_article_title a:visited {text-decoration: none;}
  		.alm_article_authors {font-weight: bold; color: #000;}
  		.article_data {margin: 3px 0 5px; width: 290px;}
  		.alm_separator {margin: 3px 0;}
  		.alm_widget {max-height: 350px; overflow: auto; background: none repeat scroll 0 0 #FFFFFF !important; border: 1px solid #20366F;}
  		.alm_widget_article {padding: 3px; text-transform: none; color: #000000;}
  		<?php
		if ($number > 1) {
			echo ".alm_borderbottom { border-bottom: 1px dotted; }\n";
		}
  		?>
  		</style>
  		<h3 class="alm_widget_title widget-title"><?php if (!empty($title)) {echo $title;} else { echo "PLoS Articles Published"; } ?> </h3>
  		<?php
  			} //end if for at least 1 article
  		?>
        <div class="alm_widget">
<?php
	foreach( $articles as $article ) {
		if (!empty($article['article'])) {
			if ( false === ( $article_query = get_transient( $article['article'] ) ) ) {
				$article_query = wp_remote_get("http://alm.plos.org/articles/".$article['article'].".json?history=1&api_key=".$options['text_apikey']."");
				set_transient( $article['article'], $article_query, 21600);
			}
			if ( false === ( $authors_query = get_transient( $article['article']."_data" ) ) ) {
				$authors_query = wp_remote_get('http://api.plos.org/search?q=id:"'.$article['article'].'"&wt=json&api_key='.$options['text_apikey'].'');
				set_transient( $article['article']."_data", $authors_query, 21600);
			}

			$authors_data = json_decode($authors_query['body']);

			$article_data = json_decode($article_query['body']);

				if (is_object($article_data)) { // check if we have article data
	?>
	<div class="clearfix alm_widget_article">
		<?php
		if (strlen(strip_tags($article_data->article->title)) > 25 ) {
			$article_title = substr(strip_tags($article_data->article->title), 0, 25)."...";
		} else {
			$article_title = $article_data->article->title;
		}
		$single_author = sizeof($authors_data->response->docs[0]->author_display);
		$article_authors = implode(', ', $authors_data->response->docs[0]->author_display);
		if (strlen($article_authors) > 59 ) {
			$article_authors = substr($article_authors, 0, 59)."...";
		} else {
			$article_authors = $article_authors;
		}

		?>
		<div class="">Article: <span class="alm_article_title"><a href="http://www.plosone.org/article/metrics/info:doi/<?php echo $article['article']; ?>" target="_blank"><?php echo $article_title; ?></a></span> </div>
		<div class="">Author<?php if ($single_author > 1 ) { echo "s";}?>: <span class="alm_article_authors"><?php echo $article_authors; ?></span> </div>
		<div class="">Published in <span class="alm_datepublished"><?php echo $authors_data->response->docs[0]->journal; ?> on <?php echo strftime('%B %e, %G', strtotime($article_data->article->published)); ?></span> </div>
		<?php
		$sources = $article_data->article->source;
		$citations = $views = $shares = $references = array();
		foreach ($sources as $source) {
			if ($source->count > 0 ){
				if ($source->source == 'CrossRef') {
					$citations['CrossRef'] = $source->source." (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'PubMed Central') {
					$citations['PubMed Central'] = $source->source." (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'Scopus') {
					$citations['Scopus'] = $source->source." (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'PubMed Central Usage Stats') {
					$views['PMC'] = "Total PMC views (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'Counter') {
					$views['PLoS'] = "Total PLoS views (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'Facebook') {
					$shares['Facebook'] = $source->source." (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'Mendeley') {
					$shares['Mendeley'] = $source->source." (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'Twitter') {
					$shares['Twitter'] = $source->source." (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'CiteULike') {
					$shares['CiteULike'] = $source->source." (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'Connotea') {
					$shares['Connotea'] = $source->source." (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'Nature') {
					$references['Nature'] = $source->source." Blogs (".number_format($source->count,0).")<br/>";
				}
				if ($source->source == 'Research Blogging') {
					$references['Research Blogging'] = $source->source." (".number_format($source->count,0).")<br/>";
				}
			}
		}
$size_citations = sizeof($citations);
$size_views = sizeof($views);
$size_shares = sizeof($shares);
$size_references = sizeof($references);

$diff = $sizes = '';
$sizes_top[] = $size_citations;
$sizes_top[] = $size_views;
$sizes_bottom[] = $size_references;
$sizes_bottom[] = $size_shares;
$max_top1 = sort($sizes_top);
$max_top = array_pop($sizes_top);
$max_bottom1 = sort($sizes_bottom);
$max_bottom = array_pop($sizes_bottom);

		?>
		<div class="article_data <?php if ($number > 1) { echo "alm_borderbottom"; } ?>">
			<div class="alm_data views">
				<div class="alm_title">Views</div>
				<div>
					<?php 
					$diff = $max_top - $size_views;
					echo $views['PLoS'];
					echo $views['PMC'];
				for ($i=1; $i <= $diff ; $i++) {  
					echo "<br/>";
				}
					?>
				</div>
			</div>
			<?php
			if (sizeof($citations) > 0 ){
			?>
			<div class="alm_data citations">
				<div class="alm_title">Citations</div>
				<?php
				$diff = $max_top - $size_citations;
				echo $citations['PubMed Central'];
				echo $citations['Scopus'];
				echo $citations['CrossRef'];
				for ($i=1; $i <= $diff ; $i++) {  
					echo "<br/>";
				}
				?>
				
			</div>
			<?php
			}

			if (sizeof($references) > 0 ){
			?>
			<div class="alm_data references">
				<div class="alm_title">References</div>
				<?php
				$diff = $max_bottom - $size_references;
				echo $references['Research Blogging'];
				echo $references['Nature'];
				for ($i=1; $i <= $diff ; $i++) {  
					echo "<br/>";
				}
				?>
				
			</div>
			<?php
			}

			if (sizeof($shares) > 0 ){
			?>
			<div class="alm_data shares">
				<div class="alm_title">Shares</div>
				<?php
				$diff = $max_bottom - $size_shares;
				echo $shares['Mendeley'];
				echo $shares['CiteULike'];
				echo $shares['Connotea'];
				echo $shares['Twitter'];
				echo $shares['Facebook'];
				for ($i=1; $i <= $diff ; $i++) {  
					echo "<br/>";
				}
				?>
				
			</div>
			<?php
			}
			?>
		</div>
	</div>
<?php 	
			} //end check if we have article data
		}
	  } ?>
        </div>
    </div>
	<?php
	}
/*
	public function update($new_instance, $old_instance) {
	//save the widget
		$instance = $old_instance;		
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['desc1'] = ($new_instance['desc1']);
		$instance['selecttag'] = ($new_instance['selecttag']);
		return $instance;
	}
*/

	public function form($instance) {
	//widgetform in backend
//		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 't1' => '', 't2' => '', 't3' => '',  'img1' => '', 'desc1' => '' ) );		
//		$title = strip_tags($instance['title']);
//		$desc1 = ($instance['desc1']);
//		$tag = ($instance['selecttag']);
		?>
		<p>The settings for this widget are in the <a href="<?php bloginfo( 'wpurl' ); ?>/wp-admin/themes.php?page=alm_widget">ALM settings page</a>.</p>
<?php
	}

}

add_action('widgets_init', 'add_alm_widget');

function add_alm_widget() {
	register_widget('alm_stats_widget');	
}
?>
