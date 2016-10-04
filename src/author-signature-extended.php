<?php

/*
 * Plugin Name: Author Signature Extended
 * Plugin URI: https://wordpress.org/plugins/author-signature-extended/
 * Description: Adds the author signature to the bottom of posts and pages. Also adds a shortcode to insert the author signature.
 * Author: Jeremy Marcus
 * Version: 1.0
 * Author URI: http://jmarc.us
 * License: GPLv3
 * Text Domain: author_signature_extended
 */

if ( ! class_exists( 'Author_Signature_Extended' ) ) {
    class Author_Signature_Extended {
        const SHORTCODE = 'author_signature';

        protected $_found_shortcode = false;
        protected $_options;

        static function init() {
            static $instance = null;

		    if ( ! $instance ) {
			    $instance = new Author_Signature_Extended();
		    }

		    return $instance;
	    }

        function __construct() {
		    add_action( 'admin_init' , array( $this , 'author_signature_init' ) );
            add_filter( 'the_content' , array( $this , 'author_signature_content' ) );
            add_filter( 'the_content' , array( $this , 'test_for_shortcode' ) , 0 );
            add_shortcode( self::SHORTCODE , array( $this , 'author_signature_shortcode' ) );
	    }

        function author_signature_init() {
            add_settings_field(
                'author_signature_settings' ,
                "<span id='author_signature_settings'>" . __( 'Author signature' , 'author_signature_extended' ) . "</span>",
                array( $this , 'author_signature_settings' ),
                'reading'
            );

            register_setting( 'reading' , 'author_signature_settings', array( $this , 'parse_options' ) );
        }

        function author_signature_content( $content ) {
            if ( ! $this->_found_shortcode ) {
                global $post;

                $options = $this->get_options();
                $signature = $this->get_signature( $options );

                if ( ( $post->post_type == 'page' ) && $options['pages_enabled'] ) {
                    $content .= $signature;
                }

                if ( ( $post->post_type == 'post' ) && $options['posts_enabled'] ) {
                    $content .= $signature;
                }
            }

            return $content;
        }

        function author_signature_settings() {
            $options = $this->get_options();

            $enabled_template = "%s
&nbsp;
<label><input name='author_signature_settings[posts_enabled]' type='checkbox' value='1' %s /> %s</label>
&nbsp;
<label><input name='author_signature_settings[pages_enabled]' type='checkbox' value='1' %s /> %s</label>";

            $enabled_control = sprintf(
                $enabled_template,
                esc_html__( 'Display on:' , 'author_signature_extended' ),
                checked( $options['posts_enabled'] , true , false ),
                esc_html__( 'posts' , 'author_signature_extended' ),
                checked( $options['pages_enabled'] , true , false ),
                esc_html__( 'pages' , 'author_signature_extended' )
            );

            $display_as_template = "<label>%s <select name='author_signature_settings[display_as]'>
    <option value='display_name' %s>%s</option>
    <option value='first_name' %s>%s</option>
    <option value='last_name' %s>%s</option>
    <option value='nice_name' %s>%s</option>
</select></label>";

            $display_as_control = sprintf(
                $display_as_template,
                esc_html__( 'Display as:' , 'author_signature_extended' ),
                selected( $options['display_as'] , 'display_name' , false ),
                esc_html__( 'Display Name' , 'author_signature_extended' ),
                selected( $options['display_as'] , 'first_name' , false ),
                esc_html__( 'First Name' , 'author_signature_extended' ),
                selected( $options['display_as'] , 'last_name' , false ),
                esc_html__( 'Last Name' , 'author_signature_extended' ),
                selected( $options['display_as'] , 'nice_name' , false ),
                esc_html__( 'Nice Name' , 'author_signature_extended' )
            );

            $prefix_suffix_template = "<label>%s <input name='author_signature_settings[%s]' type='text' value='%s' /></label>";

            $prefix_control = sprintf(
                $prefix_suffix_template,
                esc_html__( 'Prefix:' , 'author_signature_extended' ),
                'prefix',
                esc_html__( $options['prefix'] )
            );

            $suffix_control = sprintf(
                $prefix_suffix_template,
                esc_html__( 'Suffix:' , 'author_signature_extended' ),
                'suffix',
                esc_html__( $options['suffix'] )
            );

            $template = "<fieldset>
    <p>%s</p>
    <p>%s</p>
    <p>%s</p>
    <p>%s</p>
</fieldset>";

            printf(
                $template,
                $enabled_control,
                $display_as_control,
                $prefix_control,
                $suffix_control
            );
        }

        function author_signature_shortcode( $atts ) {
            $options = shortcode_atts( $this->_options , $atts );
            return $this->get_signature( $options );
        }

        function get_author_display_as( $author , $display_as ) {
            if ( $display_as == 'display_name' ) {
                return $author->display_name;
            } elseif ( $display_as == 'first_name' ) {
                return $author->first_name;
            } elseif ( $display_as == 'last_name' ) {
                return $author->last_name;
            } elseif ( $display_as == 'nice_name' ) {
                return $author->nice_name;
            } else {
                return $author->display_name;
            }
        }

        function get_options() {
            if ( null === $this->_options ) {
                $this->_options = get_option( 'author_signature_settings' );

                if ( ! is_array( $this->_options ) ) {
                    $this->_options = array();
                }

                if ( ! isset( $this->_options['posts_enabled'] ) ) {
                    $this->_options['posts_enabled'] = true;
                }

                if ( ! isset( $this->_options['pages_enabled'] ) ) {
                    $this->_options['pages_enabled'] = false;
                }

                if ( ! isset( $this->_options['display_as'] ) ) {
                    $this->_options['display_as'] = 'display_name';
                }

                if ( ! isset( $this->_options['prefix'] ) ) {
                    $this->_options['prefix'] = '&mdash; ';
                }

                if ( ! isset( $this->_options['suffix'] ) ) {
                    $this->_options['suffix'] = '';
                }

                $this->_options = apply_filters( 'author_signature_settings_filter_options' , $this->_options );
            }

            return $this->_options;
        }

        function get_signature( $options ) {
            global $post;

            $author_id = $post->post_author;
            $author = get_userdata( $author_id );

            $signature_template = "<p class='author-signature'>%s%s%s</p>";

            return sprintf(
                $signature_template,
                esc_html__( $options['prefix'] ),
                $this->get_author_display_as( $author , $options['display_as'] ),
                esc_html__( $options['suffix'] )
            );
        }

        function parse_options( $input ) {
	        $current = $this->get_options();

	        if ( ! is_array( $input ) ) {
		        $input = array();
            }

	        if ( isset( $input['posts_enabled'] ) && $input['posts_enabled'] == 1 ) {
		        $current['posts_enabled'] = true;
            } else {
		        $current['posts_enabled'] = false;
	        }

            if ( isset( $input['pages_enabled'] ) && $input['pages_enabled'] == 1 ) {
		        $current['pages_enabled'] = true;
	        } else {
		        $current['pages_enabled'] = false;
	        }

            if ( isset( $input['display_as'] ) ) {
                $current['display_as'] = $input['display_as'];
            } else {
                $current['display_as']  = 'display_name';
            }

            if ( isset( $input['prefix'] ) ) {
                $current['prefix'] = esc_html( $input['prefix'] );
            } else {
                $current['prefix']  = '&mdash; ';
            }

            if ( isset( $input['suffix'] ) ) {
                $current['suffix'] = esc_html( $input['suffix'] );
            } else {
                $current['suffix']  = '';
            }

	        return $current;
        }

        function test_for_shortcode( $content ) {
            $this->_found_shortcode = has_shortcode( $content , self::SHORTCODE );
            return $content;
	    }
    }

    Author_Signature_Extended::init();
}

?>