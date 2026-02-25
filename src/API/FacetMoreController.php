<?php
namespace BV\API;

use BV\Shortcodes\BvsResourcesShortcode;
use BV\Admin\SettingsPage;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controller REST para carregar mais opções de um facet (parâmetro fb da API: cluster:quantidade).
 */
final class FacetMoreController
{
    private const NAMESPACE = 'bv/v1';
    private const ROUTE = '/facet-more';

    public static function register(): void
    {
        add_action('rest_api_init', [__CLASS__, 'registerRoutes']);
    }

    public static function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [__CLASS__, 'handleRequest'],
            'permission_callback' => '__return_true',
            'args' => [
                'resource_type' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'facet_key' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'fb_count' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 40,
                    'minimum' => 21,
                    'maximum' => 200,
                    'sanitize_callback' => 'absint',
                ],
                'q' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => '*:*',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'fq' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Responde com mais opções do facet usando o parâmetro fb (cluster:quantidade).
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public static function handleRequest(\WP_REST_Request $request)
    {
        $resourceType = $request->get_param('resource_type');
        $facetKey = $request->get_param('facet_key');
        $fbCount = $request->get_param('fb_count');
        $q = $request->get_param('q');
        $fq = $request->get_param('fq');

        $resourcesConfig = SettingsPage::getResourcesConfig();
        $resourceConfig = null;
        foreach ($resourcesConfig as $resource) {
            if ($resource['resource'] === $resourceType) {
                $resourceConfig = $resource;
                break;
            }
        }

        if (!$resourceConfig || empty($resourceConfig['base_url'])) {
            return new \WP_REST_Response(
                ['error' => 'Recurso não configurado', 'options' => [], 'hasMore' => false],
                400
            );
        }

        $token = SettingsPage::getBvsaludToken();
        $client = new BvsaludGenericClient($resourceConfig['base_url'], $token);

        $filters = [
            'q' => $q ?: '*:*',
            'count' => 0,
            'facet' => 'true',
            'facet_field' => $facetKey,
            'fb' => $facetKey . ':' . $fbCount,
        ];
        if ($fq !== '') {
            $filters['fq'] = $fq;
        }

        $response = $client->getResources($filters);

        if (isset($response['error'])) {
            return new \WP_REST_Response(
                ['error' => $response['error'], 'options' => [], 'hasMore' => false],
                502
            );
        }

        $result = BvsResourcesShortcode::buildFilterOptionsFromApiResponse($response, $facetKey);
        $result['hasMore'] = count($result['options']) >= $fbCount;

        return new \WP_REST_Response([
            'options' => $result['options'],
            'hasMore' => $result['hasMore'],
        ], 200);
    }
}
