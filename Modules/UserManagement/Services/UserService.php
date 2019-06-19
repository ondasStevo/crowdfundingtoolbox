<?php


namespace Modules\UserManagement\Services;


use Modules\UserManagement\Repositories\UserRepository;
use Jenssegers\Agent\Agent;
use Modules\UserManagement\Entities\UserCookie;

class UserService implements UserServiceInterface
{
    private $userRepository;

    public function __construct()
    {
       $this->userRepository = new UserRepository();
    }

    public function getById($id)
    {
        return $this->userRepository->get($id);
    }

    public function createCookieIfNew($userCookie, $userId, $ip)
    {
        $agent = new Agent();
        $deviceType = 'not detected';
        if ($agent->isDesktop()) {
            $deviceType = 'desktop';
        } elseif ($agent->isMobile()) {
            $deviceType = 'mobile';
        } elseif ($agent->isPhone()) {
            $deviceType = 'phone';
        } elseif ($agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($agent->isTablet()) {
            $deviceType = 'robot';
        }

        if ($userCookie == "" && $userId == null) {
            return UserCookie::create([
                'device_type' => $deviceType,
                'browser' => $agent->browser(),
                'platform' => $agent->platform(),
                'languages' => implode(" ", $agent->languages()),
                'ip' => $ip,
            ]);
        }
    }
}