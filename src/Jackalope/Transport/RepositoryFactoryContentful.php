<?php

namespace Jackalope\Transport;

use Jackalope\Factory;
use Jackalope\FactoryInterface;
use Jackalope\Repository;
use Jackalope\Transport\Contentful\Client;
use PHPCR\RepositoryFactoryInterface;

/**
 * @author Maximilian Berghoff <Maximilian.Berghoff@mayflower.de>
 */
class RepositoryFactoryContentful implements RepositoryFactoryInterface
{
    private static $required = array(
        self::KEY_JACKALOPE_CONTENTFUL_ACCESS_TOKEN => 'Access Token to register to the service',
        self::KEY_JACKALOPE_CONTENTFUL_SPACE_ID => 'ID of the space to connect to.', // Todo: Is that a workspace?
    );
    private static $optional = array(
        'jackalope.contetnful_default_workspace' => 'string: Name of the default workspace',
        'jackalope.factory' => 'string or object: Use a custom factory class for Jackalope objects',
        'jackalope.check_login_on_server' => 'boolean: if set to empty or false, skip initial check whether repository exists. Enabled by default, disable to gain a few milliseconds off each repository instantiation.',
        );

    const KEY_JACKALOPE_CONTENTFUL_ACCESS_TOKEN = 'jackalope.contentful_access_token';
    const KEY_JACKALOPE_CONTENTFUL_SPACE_ID = 'jackalope.contentful_space_id';

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getRepository(array $parameters = null)
    {

        if (null === $parameters) {
            return null;
        }

        // check if we have all required keys
        $present = array_intersect_key(self::$required, $parameters);
        if (count(array_diff_key(self::$required, $present))) {
            return null;
        }
        $defined = array_intersect_key(array_merge(self::$required, self::$optional), $parameters);
        if (count(array_diff_key($defined, $parameters))) {
            return null;
        }

        if (isset($parameters['jackalope.factory'])) {
            $factory = $parameters['jackalope.factory'] instanceof FactoryInterface
                ? $parameters['jackalope.factory'] : new $parameters['jackalope.factory'];
        } else {
            $factory = new Factory();
        }

        /** @var Client $transport */
        $transport = $factory->get('Transport\Contentful\Client', array());
        $transport->setAccessToken($parameters[self::KEY_JACKALOPE_CONTENTFUL_ACCESS_TOKEN]);
        $transport->setSpaceId($parameters[self::KEY_JACKALOPE_CONTENTFUL_SPACE_ID]);

        return new Repository($factory, $transport, array());
    }

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function getConfigurationKeys()
    {
        return array_merge(self::$required, self::$optional);
    }
}
