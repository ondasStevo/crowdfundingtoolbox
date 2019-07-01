<?php

namespace Modules\Statistics\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Payment\Entities\Donation;
use Modules\UserManagement\Entities\PortalUser;

class StatsDonorRepository implements StatsDonorRepositoryInterface
{

    protected $model;

    function __construct()
    {
        $this->model = PortalUser::class;
    }

    public function getDonors($from, $to, $monthly)
    {
        // get date of last donation
        $lastDonationAt = Donation::query()
            ->select('portal_user_id', DB::raw('MAX(created_at) as last_donation_at'))
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->groupBy('portal_user_id');

        //if monthly is set, filter lastDonationAt
        if ($monthly !== null) {
            $lastDonationAt = $lastDonationAt->where('is_monthly_donation', $monthly);
        }
        // last donation detail
        $lastDonation = Donation::query()
            ->select('portal_user_id', 'donation as last_donation_value', 'is_monthly_donation as last_donation_monthly',
                'type as last_donation_type', 'created_at');

        // donations_sum
        $donationsSum = Donation::query()
            ->select('portal_user_id', DB::raw('sum(donation) as donations_sum'),
                DB::raw('MIN(created_at) as first_donation_at'))
            ->groupBy('portal_user_id');


        return PortalUser::query()
            ->joinSub($lastDonationAt, 'latest_donation_group', function ($join) use ($lastDonation, $from, $to) {
                $join->on('portal_users.id', '=', 'latest_donation_group.portal_user_id');
            })
            ->joinSub($lastDonation, 'latest_donation', function ($join) use ($from, $to) {
                $join->on('last_donation_at', '=', 'latest_donation.created_at');
            })
            ->joinSub($donationsSum, 'donations_sum', function ($join) {
                $join->on('portal_users.id', '=', 'donations_sum.portal_user_id');
            })
            ->orderBy('last_donation_at', 'DESC')
            ->with('user.userDetail')
            ->get();
    }


}