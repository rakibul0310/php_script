<?php

namespace App\Providers;

use App\Helper;
use App\Models\Blogs;
use App\Models\Reports;
use App\Models\Updates;
use App\Models\Deposits;
use App\Models\TaxRates;
use App\Models\Languages;
use App\Models\Categories;
use App\Models\Advertising;
use App\Models\Withdrawals;
use App\Models\AdminSettings;
use App\Models\Gift;
use App\Models\LiveStreamings;
use App\Models\PaymentGateways;
use App\Models\VerificationRequests;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 */
	public function register(): void
	{
	}

	/**
	 * Bootstrap any application services.
	 */
	public function boot()
	{
		try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
			return false;
        }
		
		// Admin Settings
		$settings = AdminSettings::first();

		// Updates pending count on Panel Admin
		$updatesPendingCount = Updates::selectRaw('COUNT(id) as total')->whereStatus('pending')->pluck('total')->first();

		// Deposits pending count on Panel Admin
		$depositsPendingCount = Deposits::selectRaw('COUNT(id) as total')->whereStatus('pending')->pluck('total')->first();

		// Reports on Panel Admin
		$reports = Reports::selectRaw('COUNT(id) as total')->pluck('total')->first();

		// Withdrawals pending count on Panel Admin
		$withdrawalsPendingCount = Withdrawals::selectRaw('COUNT(id) as total')->whereStatus('pending')->pluck('total')->first();

		// Verification Requests count on Panel Admin
		$verificationRequestsCount = VerificationRequests::selectRaw('COUNT(id) as total')->whereStatus('pending')->pluck('total')->first();

		// Payment Gateways
		$paymentsGateways = PaymentGateways::all();

		// Payment Gateways Subscription, Tips, PPV
		$paymentGatewaysSubscription = PaymentGateways::where('enabled', '1')->whereSubscription('yes')->get();

		// Blogs Count
		$blogsCount = Blogs::count();

		// Categories Count
		$categoriesCount = Categories::count();

		// Al categories
		$categoriesFooter = Categories::where('mode', 'on')->orderBy('name')->take(6)->get();

		// Languages
		$languages = Languages::orderBy('name')->get();

		// Tax Rates
		$taxRatesCount = TaxRates::whereStatus('1')->count();

		// Show Section My Cards
		$showSectionMyCards = Helper::showSectionMyCards();

		// Get Current Live
		$getCurrentLiveCreators = LiveStreamings::whereType('normal')
			->where('updated_at', '>', now()->subMinutes(5))
			->whereStatus('0')
			->pluck('user_id')
			->toArray();

		// Get Advertising 
		$advertising = Advertising::where('expired_at', '>', now())
			->whereStatus(1)
			->inRandomOrder()
			->take(1)
			->get();

		// Get Gifts
		$gifts = Gift::whereStatus(true)->orderBy('price', 'asc')->get();

		view()->share(
			compact(
				'settings',
				'updatesPendingCount',
				'depositsPendingCount',
				'reports',
				'withdrawalsPendingCount',
				'verificationRequestsCount',
				'paymentsGateways',
				'blogsCount',
				'categoriesCount',
				'categoriesFooter',
				'languages',
				'showSectionMyCards',
				'paymentGatewaysSubscription',
				'taxRatesCount',
				'getCurrentLiveCreators',
				'advertising',
				'gifts'
			)
		);
	}
}
