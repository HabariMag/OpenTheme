<?php

define( 'THEME_CLASS', 'opentheme' );

class opentheme extends Theme
{	
		public function action_init_theme()
	{
		// Apply Format::autop() to post content...
		Format::apply( 'autop', 'post_content_out' );
		// Apply Format::autop() to comment content...
		Format::apply( 'autop', 'comment_content_out' );
		// Truncate content excerpt at "more" …
		Format::apply_with_hook_params( 'more', 'post_content_excerpt', '','', 1 );
	}
	
	/**
	 * Add some variables to the template output
	 */
	public function add_template_vars()
	{
		$locale =Options::get( 'locale' );
		if ( file_exists( Site::get_dir( 'theme', true ). $locale . '.css' ) ){
			$this->assign( 'localized_css',  $locale . '.css' );
		}
		else {
			$this->assign( 'localized_css', false );
		}
		
		if( !$this->template_engine->assigned( 'pages' ) ) {
			$this->assign('pages', Posts::get( array( 'content_type' => 'page', 'status' => Post::status('published'), 'nolimit' => 1 ) ) );
		}
		$this->assign( 'post_id', ( isset($this->post) && $this->post->content_type == Post::type('page') ) ? $this->post->id : 0 );
		parent::add_template_vars();
		
		switch ($_SERVER['SERVER_NAME']) {
			case 'bowg.de':
				$this->assign('site', array('header' => 'uploadparty.png', 'fav' => 'uploadparty.ico', 'style' => 'uploadparty.css','ga' => '3','bs' => '6417'));
				break;
			case 'openruhr.de':
				$this->assign('site',  array('header' => 'openruhr.png', 'fav' => 'openruhr.ico', 'style' => 'openruhr.css','ga' => '2','bs' => '6417'));
				break;
			default :
				$this->assign('site',  array('header' => 'openruhr.png', 'fav' => 'openruhr.ico', 'style' => 'openruhr.css','ga' => '2','bs' => '6417'));		}
	}
		
	/**
	 * Convert a post's tags array into a usable list of links
	 *
	 * @param array $array The tags array from a Post object
	 * @return string The HTML of the linked tags
	 */
	public function filter_post_tags_out($array)
	{
		if ( ! is_array( $array ) ) {
			$array = array ( $array );
		}
		$fn = create_function('$a,$b', 'return "<a href=\\"" . URL::get("display_entries_by_tag", array( "tag" => $b) ) . "\\" rel=\\"tag\\">" . $a . "</a>";');
		$array = array_map($fn, $array, array_keys($array));
		$out = implode(' ', $array);
		return $out;
	}

	public function theme_post_comments_link($theme, $post, $zero, $one, $more)
	{
		$c = $post->comments->approved->count;
		switch ($c) {
			case '0':
				return $zero;
				break;
			case '1':
				return str_replace( '%s', '1', $one );
				break;
			default :
				return str_replace( '%s', $c, $more);
		}
	}

/*	public function filter_post_content_excerpt($return)
	{
		return strip_tags($return);
	}*/

	public function theme_search_prompt( $theme, $criteria, $has_results )
	{
		$out =array();
		$keywords =explode(' ',trim($criteria));
		foreach ($keywords as $keyword) {
			$out[]= '<a href="' . Site::get_url( 'habari', true ) .'search?criteria=' . $keyword . '" title="' . _t( 'Search for ' ) . $keyword . '">' . $keyword . '</a>';
		}
		
		if ( sizeof( $keywords ) > 1 ) {
			if ( $has_results ) {
				return sprintf( _t( 'Search results for \'%s\'' ), implode(' ',$out) );
				exit;
			}
			return sprintf( _t('No results found for your search \'%1$s\'') . '<br>'. _t('You can try searching for \'%2$s\''), $criteria, implode('\' or \'',$out) );
		}
		else {
			return sprintf( _t( 'Search results for \'%s\'' ), $criteria );
			exit;
		}
		return sprintf( _t( 'No results found for your search \'%s\'' ), $criteria );

	}
	
	public function theme_search_form( $theme )
	{
		return $theme->fetch('searchform');
	}
	
	/**
	 * Returns an unordered list of all used Tags
	 */
	public function theme_show_tags ( $theme )
	{
		$limit = self::TAGS_COUNT;
		$sql ="
			SELECT t.tag_slug AS slug, t.tag_text AS text, count(tp.post_id) as ttl
			FROM {tags} t
			INNER JOIN {tag2post} tp
			ON t.id=tp.tag_id
			INNER JOIN {posts} p
			ON p.id=tp.post_id AND p.status = ?
			GROUP BY t.tag_slug
			ORDER BY t.tag_text
			LIMIT {$limit}
		";
		$tags = DB::get_results( $sql, array(Post::status('published')) );
		foreach ($tags as $index => $tag) {
			$tags[$index]->url = URL::get( 'display_entries_by_tag', array( 'tag' => $tag->slug ) );
		}
		$theme->taglist = $tags;
		
		return $theme->fetch( 'taglist' );
	}
}
?>
