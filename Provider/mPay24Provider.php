<?php

namespace Netbull\Mpay24Bundle\Provider;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

use Mpay24\Mpay24;
use Mpay24\Mpay24Config;

/**
 * Class MPay24Provider
 * @package Netbull\Mpay24Bundle\Provider
 */
class MPay24Provider
{
    const TOKEN_NAME = '_token';

    /**
     * @var Mpay24
     */
    private $instance;

    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var Session
     */
    private $session;

    /**
     * mPay24Provider constructor.
     * @param array         $options
     * @param RequestStack  $requestStack
     * @param string        $defaultLocale
     * @param Session       $session
     */
    public function __construct( array $options, RequestStack $requestStack, $defaultLocale, Session $session )
    {
        $config = new Mpay24Config($options);
        $this->instance = new Mpay24($config);

        $this->request  = $requestStack->getCurrentRequest();
        $this->locale   = ( $this->request ) ? $this->request->getLocale() : $defaultLocale;
        $this->session  = $session;
    }

    /**
     * @return Mpay24
     */
    public function getInstance()
    {
        return $this->instance;
    }

    /**
     * @param array $options
     * @return array
     */
    public function createToken( array $options = [] )
    {
        $defaultOptions = [ 'language' => strtoupper($this->locale) ];
        $options = array_merge($defaultOptions, $options);

        $tokenData = [
            'token'     => $this->instance->token('CC', $options),
            'createdAt' => new \DateTime('now')
        ];

        return $tokenData;
    }

    /**
     * @param string    $name
     * @param array     $options
     * @return \Mpay24\Responses\CreatePaymentTokenResponse
     */
    public function createAndStoreToken( $name = self::TOKEN_NAME, array $options = [] )
    {
        $tokenData = $this->createToken($options);
        $this->session->set($name, $tokenData);

        return $tokenData['token'];
    }

    /**
     * @param string    $name
     * @param array     $options
     * @return mixed|\Mpay24\Responses\CreatePaymentTokenResponse
     */
    public function getToken( $name = self::TOKEN_NAME, array $options = [] )
    {
        return ( $this->isTokenValid($name) ) ? $this->session->get($name)['token'] : $this->createAndStoreToken($name, $options);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isTokenValid( $name = self::TOKEN_NAME )
    {
        $testDate = new \DateTime('-10 minutes');
        $token = $this->session->get($name);

        return ( $token && $token['createdAt'] > $testDate );
    }
}
