<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * True if profile has any of the given permission codes.
     *
     * @param array<string,mixed> $profile
     * @param string|list<string> $codes
     */
    protected function profileCan(array $profile, string|array $codes): bool
    {
        $owned = $profile['permissions'] ?? [];
        if (! is_array($owned)) {
            return false;
        }
        foreach ((array) $codes as $code) {
            if (in_array($code, $owned, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect when profile missing or lacking permission.
     *
     * @param array<string,mixed>|null $profile
     * @param string|list<string>      $codes
     */
    protected function denyUnlessCan(?array $profile, string|array $codes)
    {
        if ($profile === null || $profile === []) {
            return redirect()->to('/login');
        }
        if (! $this->profileCan($profile, $codes)) {
            return redirect()->to('/')->with('error', 'Anda tidak memiliki hak akses');
        }

        return null;
    }
}
