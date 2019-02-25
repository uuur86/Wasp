<?php

/**
 * WP Settings framework
 *
 * @author Uğur Biçer <uuur86@yandex.com>
 * @copyright 2018, Uğur Biçer
 * @license GPLv3
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class wp_admin_setting_pages {

    protected $title;

    protected $desc;

    protected $domain;

    protected $page_name;

    protected $settings_name;

    protected $fields;

    protected $sanitize;

    protected $sections;

    protected $section;



    public function __construct( $page, $settings, $domain ) {

        if( !isset( $page ) || $page === false || empty( $page ) )
            return false;

        $this->page_name = $page;
        $this->settings_name = $settings . '_settings'; // TODO : will be change soon
        $this->domain = $domain;

        if( false == get_option( $this->settings_name ) ) {
            add_option( $this->settings_name, [] );
        }

        return $this;
    }


    public function add_section( $name, $title, $desc ) {

        if( empty( $name ) )
            return;

        $this->section = $name;

        $this->sections[ $this->section ] = [
            'title' => $title,
            'desc' => $desc,
        ];

        add_settings_section(
            $this->section . '_section', // Section Title
            '',//esc_html__( $title, $this->domain ), // Callback for an optional title
            array( $this, 'settings_field_section_callback' ), // Admin page to add section to
            $this->section
        );
    }



    public function settings_field_section_callback() {
        $current = current( $this->sections );
        echo '<h2 class="title">' . esc_html__( $current[ 'title' ], $this->domain ) . '</h2>';
        echo '<p class="description">' . esc_html__( $current[ 'desc' ], $this->domain ) . '</p>';
        next( $this->sections );
    }



    protected function get_settings( $name ) {
        if( ( $options = get_option( $this->settings_name, false ) ) !== false ) {
            if( isset( $options[ $name ] ) )
                return $options[ $name ];
        }
        return '';
    }



    public function add_new_field( $type, $id, $label, $options = null, $sanitize = 'text_field' ) {
        $field_type_callback = 'settings_field_' . $type . '_callback';

        if( in_array( $type, array( 'radio', 'select' ) ) )
            $this->sanitize[ $id ] = array( 'type' => 'options', 'values' => array_keys( $options ) );
        else
            $this->sanitize[ $id ] = array( 'type' => $sanitize, 'regex' => $options );

        $field_args = [
            'label' => esc_html__( $label, $this->domain ),
            'name' => $id
        ];

        if( is_array( $options ) ) {
            $field_args[ 'options' ] = $options;
        }

        add_settings_field(
            $this->settings_name . '_' . $id,
            esc_html__( $label, $this->domain ),
            array( $this, $field_type_callback ),
            $this->section,
            $this->section . '_section',
            $field_args
        );
    }



    public function register() {

        register_setting(
            $this->settings_name . '',
            $this->settings_name . '',
            array( $this, 'settings_input_middleware' )
        );
    }



    public function settings_field_text_input_callback( $args ) {
        $value = $this->get_settings( $args[ 'name' ] );

        if( !empty( $value ) ) {
            $value = esc_html( $value );
        }
        echo '<input type="text" id="' . $this->settings_name . '_' . $args[ 'name' ] . '_input_text" name="' . $this->settings_name . '[' . $args[ 'name' ] . ']" value="' . $value . '" placeholder="' . $args[ 'label' ] . '"/>';
    }



    public function settings_field_select_callback( $args ) {
        $value = $this->get_settings( $args[ 'name' ] );

        if( !empty( $value ) ) {
            $value = esc_html( $value );
        }

        $options = '';

        if( is_array( $args[ 'options' ] ) ) {
            foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
                $options .= '<option value="' . $opt_key . '" ' . selected( $opt_key, $value, false ) . '>' . $opt_val . '</option>';
            }
        }

        echo '<select id="' . $this->settings_name . '_' . $args[ 'name' ] . '_input_text" name="' . $this->settings_name . '[' . $args[ 'name' ] . ']">';
        echo $options;
        echo '</select>';
    }



    public function settings_field_checkbox_callback( $args ) {
        $value = $this->get_settings( $args[ 'name' ] );

        $options = '<fieldset>';

        if( is_array( $args[ 'options' ] ) ) {
            foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
                $value_ = '';
                if( !empty( $value[ $opt_key ] ) ) {
                    $value_ = esc_html( $value[ $opt_key ] );
                }

                $id = $this->settings_name . '_' . $args[ 'name' ] . '_input_checkbox_' . $opt_key;
                $name = $this->settings_name . '[' . $args[ 'name' ] . '][' . $opt_key . ']';

                $options .= '<label for="' . $id . '">';
                $options .= '<input type="checkbox"  name="' . $name . '" id="' . $id . '" value="' . $opt_key . '" ' . checked( $opt_key, $value_, false ) . '/>';
                $options .= $opt_val . '</label> &nbsp;<br/>';
            }
        }

        $options .= '</fieldset>';

        echo $options;
    }


    public function settings_field_radio_callback( $args ) {
        $value = $this->get_settings( $args[ 'name' ] );

        if( !empty( $value ) ) {
            $value = esc_html( $value );
        }

        $options = '<fieldset>';

        if( is_array( $args[ 'options' ] ) ) {
            foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
                $id = $this->settings_name . '_' . $args[ 'name' ] . '_input_radio_' . $opt_key;
                $name = $this->settings_name . '[' . $args[ 'name' ] . ']';

                $options .= '<label for="' . $id . '">';
                $options .= '<input type="radio"  name="' . $name . '" id="' . $id . '" value="' . $opt_key . '" ' . checked( $opt_key, $value, false ) . '/>';
                $options .= $opt_val . '</label> &nbsp;<br/>';
            }
        }
        
        $options .= '</fieldset>';

        echo $options;
    }


    public function settings_input_middleware( $inputs ) {

        foreach( $this->sanitize as $input_key => $input_value ) {

            if( !isset( $input_value[ 'type' ] ) )
                continue;

            if( $input_value[ 'type' ] == 'regex' && isset( $input_value[ 'regex' ] ) ) {
                if( preg_match( "#^" . $input_value[ 'regex' ] . "$#ui", $inputs[ $input_key ], $matched ) )
                    $inputs[ $input_key ] = $matched[ 0 ];
                else
                    $inputs[ $input_key ] = '';
                continue;
            }

            if( $input_value[ 'type' ] == 'options' && is_array( $input_value[ 'values' ] ) ) {
                if( !in_array( $inputs[ $input_key ], $input_value[ 'values' ] ) ) {
                    unset( $inputs[ $input_key ] );
                }
                continue;
            }

            $func_name = 'sanitize_' . $input_value;

            if( !function_exists( $func_name ) )
                continue;

            $inputs[ $input_key ] = call_user_func( $func_name, $inputs[ $input_key ] );
        }

        return $inputs;
    }



    public function run() {
        // Display necessary hidden fields for settings
        settings_fields( $this->settings_name );
        // Display the settings sections for the page
        foreach( $this->sections as $sec_name => $sec_value ) {
            do_settings_sections( $sec_name );
        }

        // Default Submit Button
        submit_button();
    }

}


