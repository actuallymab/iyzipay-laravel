<?php

namespace Iyzico\IyzipayLaravel;

use Iyzico\IyzipayLaravel\Exceptions\Card\CardRemoveException;
use Iyzico\IyzipayLaravel\Models\CreditCard;
use Iyzico\IyzipayLaravel\Models\Subscription;
use Iyzico\IyzipayLaravel\Models\Transaction;
use Iyzico\IyzipayLaravel\StorableClasses\BillFields;
use Iyzico\IyzipayLaravel\StorableClasses\Plan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Iyzico\IyzipayLaravel\IyzipayLaravelFacade as IyzipayLaravel;

trait Payable
{

    /**
     * @param $value
     */
    public function setBillFieldsAttribute(BillFields $value)
    {
        $this->attributes['bill_fields'] = (string)$value;
    }

    /**
     * @param $value
     *
     * @return object
     */
    public function getBillFieldsAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }

        return (new \JsonMapper())->map(json_decode($value), new BillFields());
    }

    /**
     * Credit card relationship for the payable model
     *
     * @return HasMany
     */
    public function creditCards(): HasMany
    {
        return $this->hasMany(CreditCard::class, 'billable_id');
    }

    /**
     * Transaction relationship for the payable model
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'billable_id');
    }

    /**
     * Payable can has many subscriptions
     *
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'billable_id');
    }

    /**
     * Add credit card for payable
     *
     * @param array $attributes
     * @return CreditCard
     */
    public function addCreditCard(array $attributes = []): CreditCard
    {
        return IyzipayLaravel::addCreditCard($this, $attributes);
    }

    /**
     * Remove credit card credentials from the payable
     *
     * @param CreditCard $creditCard
     * @return bool
     * @throws CardRemoveException
     */
    public function removeCreditCard(CreditCard $creditCard): bool
    {
        if ( ! $this->creditCards->contains($creditCard)) {
            throw new CardRemoveException('This card does not belong to member!');
        }

        return IyzipayLaravel::removeCreditCard($creditCard);
    }

    /**
     * Single payment for the payable
     *
     * @param Collection $products
     * @param string $currency
     * @param int $installment
     * @param bool $subscription
     * @param null $creditCard
     * @return Transaction
     */
    public function pay(Collection $products, $currency = 'TRY', $installment = 1, $subscription = false, $creditCard = null): Transaction
    {
        return IyzipayLaravel::singlePayment($this, $products, $currency, $installment, $subscription, $creditCard);
    }

    /**
     * Subscribe to a plan.
     * @param Plan $plan
     */
    public function subscribe(Plan $plan): void
    {
        Model::unguard();

        $this->subscriptions()->save(
            new Subscription([
                'next_charge_amount' => $plan->price,
                'currency'           => $plan->currency,
                'next_charge_at'     => Carbon::now()->addDays($plan->trialDays)->startOfDay(),
                'plan'               => $plan
            ])
        );

        $this->paySubscription();

        Model::reguard();
    }

    /**
     * Check if payable subscribe to a plan
     *
     * @param Plan $plan
     * @return bool
     */
    public function isSubscribeTo(Plan $plan): bool
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->plan == $plan)
            {
                return $subscription->next_charge_at > Carbon::today()->startOfDay();
            }
        }

        return false;
    }

    /**
     * Payment for the subscriptions of payable
     */
    public function paySubscription()
    {
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->canceled() || $subscription->next_charge_at > Carbon::today()->startOfDay()) {
                continue;
            }

            if ($subscription->next_charge_amount > 0) {
                $transaction = $this->pay(collect([$subscription->plan]), $subscription->plan->currency, 1, true);
                $transaction->subscription()->associate($subscription);
                $transaction->save();
            }

            $subscription->next_charge_at = $subscription->next_charge_at->addMonths(($subscription->plan->interval == 'yearly') ? 12 : 1);
            $subscription->save();
        }
    }

    /**
     * Check payable can have bill fields.
     *
     * @return bool
     */
    public function isBillable(): bool
    {
        return ! empty($this->bill_fields);
    }
}
