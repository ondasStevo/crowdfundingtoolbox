import {Payment} from './payment';
import {PortalUser} from '../../portal-users/models/portal-user';

export class Donation {
    amount: number;
    id: number;
    referral_widget_id: number;
    is_monthly_donation: boolean;
    payment: Payment;
    payment_method: string;
    portal_user: PortalUser;
    status: string;
}
