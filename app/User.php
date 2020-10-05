<?php

namespace App;

use Exception;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    public function getTwitterHelper(): TwitterHelper {
        return new TwitterHelper($this);
    }

    public function getTwitterAccount(): TwitterAccount {
        return TwitterAccount::where('user_id', $this->id)->exists() ? TwitterAccount::where('user_id', $this->id)->first() : null;
    }

    public function getStripeHelper(): StripeHelper {
        return new StripeHelper($this);
    }

    public function hasStripeAccount(): bool {
        return $this->getStripeHelper()->getCustomerAccount() != null;
    }

    public function StripeConnect()
    {
        return StripeConnect::where('user_id', $this->id)->exists() ? StripeConnect::where('user_id', $this->id)->first() : null;
    }

    public function getPlanExpiration() {
        try {
            $subscription = $this->getStripeHelper()->getExpressSubscription();
            if($subscription == null || $subscription->status != 'active') return null;
            return $subscription->current_period_end;
        }catch(Exception $e) {
            return null;
        }
        return null;
    }

    public function canAcceptPayments(): bool {
        return $this->getStripeHelper()->isExpressUser() && $this->getStripeHelper()->hasExpressPlan();
    }

}
