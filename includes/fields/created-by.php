<?php

class GravityView_Field_Created_By extends GravityView_Field {

	var $name = 'created_by';

	function field_options( $field_options, $template_id, $field_id, $context, $input_type ) {

		$field_options['name_display'] = array(
			'type' => 'select',
			'label' => __( 'User Format', 'gravity-view' ),
			'desc' => __( 'How should the User information be displayed?', 'gravity-view'),
			'choices' => array(
				'display_name' => __('Display Name (Example: "Ellen Ripley")', 'gravity-view'),
				'user_login' => __('Username (Example: "nostromo")', 'gravity-view'),
				'ID' => __('User ID # (Example: 426)', 'gravity-view'),
			),
			'default' => 'display_name'
		);

		return $field_options;
	}

}

new GravityView_Field_Created_By;
