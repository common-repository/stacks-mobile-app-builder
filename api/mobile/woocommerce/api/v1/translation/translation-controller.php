<?php

/**
 * Description of translation-controller
 *
 * @author Ahmed
 */
class Stacks_TranslationController extends Stacks_AbstractController {

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'translations';

    public function register_routes() {
        register_rest_route($this->get_api_endpoint(), $this->rest_base, [ // V3
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_translations_available'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
                'args'                => $this->get_collection_params(),
            ],
            'schema' => array($this, 'get_public_item_schema'),
        ]);

        $existing_translations = Stacks_PolylangIntegration::get_locale_for_existing_languages();
        if (!empty($existing_translations)) {
            foreach ($existing_translations as $translation) {
                // Example ar.json or en.json
                register_rest_route($this->get_api_endpoint(), $this->rest_base . '/' . $translation . '.json', [ // V3
                    [
                        'methods'             => WP_REST_Server::READABLE,
                        'callback'            => array($this, 'get_locale_translation'),
                        'permission_callback' => array($this, 'get_items_permissions_check'),
                        'args'                => $this->get_collection_params(),
                    ],
                    'schema' => array($this, 'get_public_item_schema'),
                ]);
            }
        }
    }

    /**
     * Get translation
     * 
     * @param WP_REST_Request $request
     * 
     * @return array
     */
    public function get_locale_translation(WP_REST_Request $request) {
        if (!Stacks_PolylangIntegration::is_installed()) {
            return false;
        }
        $route = $request->get_route();

        $req_locale = rtrim(str_replace('/' . trailingslashit($this->get_api_endpoint()) . $this->rest_base . '/', '', $route), '.json');

        add_filter('locale', function ($locale) use ($req_locale) {
            return $req_locale;
        });

        $strings = Stacks_App_Strings_Polylang_Translation::get_app_stings_translated();

        return $strings;
    }


    /**
     * Get translations available in Polylang
     * 
     * @param WP_REST_Request $request
     * 
     * @return array
     */
    public function get_translations_available(WP_REST_Request $request) {
        $existing_translations = Stacks_PolylangIntegration::get_existing_languages();

        return $this->return_success_response($existing_translations);
    }

    /**
     * Options to pass to limit the response
     * 
     * @return array
     */
    protected function get_collection_params() {
        return [];
    }

    /**
     * the schema for the request 
     * 
     * @return array
     */
    protected function get_public_item_schema() {
        return [
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            'title'                => 'Translation',
            'type'                 => 'object',
            'properties'           => [
                'id' => [
                    'description'  => esc_html__('Unique identifier for the object.', 'plates'),
                    'type'         => 'integer',
                    'context'      => ['view'],
                    'readonly'     => true,
                ]
            ],
        ];
    }
}
