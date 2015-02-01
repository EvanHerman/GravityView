<?php

/**
 * Class GravityView_Recent_Entries_Widget
 * @since 1.6
 */
class GravityView_Recent_Entries_Widget extends WP_Widget {


	function __construct( ) {

		$name = __('GravityView - Recent Entries', 'gravityview');

		$widget_options = array(
			'description' => __( 'Display the most recent entries for a View', 'gravityview' ),
		);

		parent::__construct( 'gv_recent_entries', $name, $widget_options );

		add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts') );

	}

	function admin_enqueue_scripts() {
		global $pagenow;

		if( $pagenow === 'widgets.php' ) {
			GravityView_Admin_Views::enqueue_gravity_forms_scripts();
		}

	}

	/**
	 * @since 1.6
	 * @see WP_Widget::widget()
	 */
	function widget( $args, $instance ) {

		$args['id']        = ( isset( $args['id'] ) ) ? $args['id'] : 'gv_recent_entries';
		$instance['title'] = ( isset( $instance['title'] ) ) ? $instance['title'] : '';

		$title = apply_filters( 'widget_title', $instance[ 'title' ], $instance, $args['id'] );

		echo $args['before_widget'];

		if ( !empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		do_action( 'gravityview/widget/recent-entries/before_widget', $args, $instance );

		$view_id = $instance['view_id'];

		// Get the settings for the View ID
		$view_settings = gravityview_get_template_settings( $view_id );

		// Merge the view settings with the defaults
		$view_settings = wp_parse_args( $view_settings, GravityView_View_Data::get_default_args() );

		$form_id = gravityview_get_form_id( $view_id );
		$form = gravityview_get_form( $form_id );

		$view_settings['page_size'] = $instance['number'];

		$results = GravityView_frontend::get_view_entries( $view_settings, $form_id );

		$list_item = array();

		foreach( $results['entries'] as $entry ) {

			$link = GravityView_API::entry_link( $entry, $view_id );
			$text = $instance['link_format'];

			$output = gravityview_get_link( $link, $text );

			if( !empty( $instance['after_link'] ) ) {
				$output .= '<div>'.$instance['after_link'].'</div>';
			}

			$output = Gravityview_API::replace_variables( $output, $form, $entry );

			$list_item[] = $output;
		}

		echo '<ul><li>'. implode( '</li><li>', $list_item ) . '</li></ul>';

		do_action( 'gravityview/widget/recent-entries/after_widget', $args, $instance );

		echo $args['after_widget'];
	}

	/**
	 * @since 1.6
	 * @see WP_Widget::update()
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['number'] = empty( $new_instance['number'] ) ? 10 : absint( $new_instance['number'] );
		$instance['view_id'] = (int) $new_instance['view_id'];
		$instance['link_format'] = $new_instance['link_format'];
		$instance['after_link'] = $new_instance['after_link'];


		return $instance;
	}

	/**
	 * @since 1.6
	 * @see WP_Widget::form()
	 */
	public function form( $instance ) {

		// Set up some default widget settings.
		$defaults = array(
			'title' 			=> __('Recent Entries'),
			'view_id'           => NULL,
			'number'            => 10,
			'link_format'       => __('Entry #{entry_id}', 'gravityview'),
			'after_link'        => ''
		);

		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Title -->
		<p xmlns="http://www.w3.org/1999/html">
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'edd' ) ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>

		<!-- Download -->
		<?php
		$args = array(
			'post_type'      => 'gravityview',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);
		$views = get_posts( $args );

		// If there are no views set up yet, we get outta here.
		if( empty( $views ) ) {
			echo '<div id="select_gravityview_view"><div class="wrap">'. GravityView_Post_Types::no_views_text() .'</div></div>';
			return;
		}

		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'view_id' ) ); ?>"><?php esc_html_e('Select a View', 'gravityview'); ?></label>
			<select class="widefat gv-recent-entries-select-view" name="<?php echo esc_attr( $this->get_field_name( 'view_id' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'view_id' ) ); ?>">
				<option value=""><?php esc_html_e( '&mdash; Select a View as Entries Source &mdash;', 'gravityview' ); ?></option>
				<?php

					foreach( $views as $view ) {
						$title = empty( $view->post_title ) ? __('(no title)', 'gravityview') : $view->post_title;
						echo '<option value="'. $view->ID .'"'.selected( absint( $instance['view_id'] ), $view->ID ).'>'. esc_html( sprintf('%s #%d', $title, $view->ID ) ) .'</option>';
					}

				?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>">
				<span><?php _e( 'Number of entries to show:', 'gravityview' ); ?></span>
			</label>
			<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" value="<?php echo intval( $instance['number'] ); ?>" size="3" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'link_format' ); ?>">
				<span><?php _e( 'Text of the link', 'gravityview' ); ?></span>
			</label>
			<input id="<?php echo $this->get_field_id( 'link_format' ); ?>" name="<?php echo $this->get_field_name( 'link_format' ); ?>" type="text" value="<?php echo esc_attr( $instance['link_format'] ); ?>" class="widefat merge-tag-support mt-position-right mt-hide_all_fields" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'after_link' ); ?>">
				<span><?php _e( 'Text of the link', 'gravityview' ); ?></span>
			</label>
			<textarea id="<?php echo $this->get_field_id( 'after_link' ); ?>" name="<?php echo $this->get_field_name( 'after_link' ); ?>" class="widefat merge-tag-support mt-position-right mt-hide_all_fields"><?php echo esc_textarea( $instance['after_link'] ); ?></textarea>
		</p>

		<script>
			<?php
				$form_id = 17;

				$form = GFFormsModel::get_form_meta( $form_id );

				GFCommon::gf_global();
				GFCommon::gf_vars();

				echo 'gf_vars.mergeTags = '.json_encode( GFCommon::get_merge_tags( $form['fields'], '', false ) ).';';
				echo 'var form = '. json_encode( $form ) . ';';
			?>

			jQuery( document).ready(function( $ ) {
				$('input[id~=link_format]').focus(function() {
					window.gfMergeTagsObj.init();
				});
			});
		</script>

		<?php do_action( 'gravityview_recent_entries_widget_form' , $instance ); ?>

	<?php }

}

/**
 * Register GravityView widgets
 *
 * @since 1.6
 * @return void
 */
function gravityview_register_widgets() {

	register_widget( 'GravityView_Recent_Entries_Widget' );
	
}

add_action( 'widgets_init', 'gravityview_register_widgets' );