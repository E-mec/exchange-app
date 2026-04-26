# Withdrawal Flow Implementation Summary

## Changes Implemented

### 1. **PayoutProvider Interface** ([PayoutProvider.php](Modules/Wallet/app/Interfaces/PayoutProvider.php))
- New interface to abstract payment provider implementations
- Enables flexibility to swap between different payment gateways (Paystack, Flutterwave, Stripe, etc.)
- Methods:
  - `execute(array $data): array` - Execute payout to destination
  - `getName(): string` - Get provider name

### 2. **StubPayoutProvider Implementation** ([StubPayoutProvider.php](Modules/Wallet/app/Providers/StubPayoutProvider.php))
- Stub implementation to be replaced with actual provider
- Contains placeholder for real API calls
- Ready for integration with Paystack, Flutterwave, or other providers

### 3. **ProcessWithdrawalJob** ([ProcessWithdrawalJob.php](Modules/Wallet/app/Jobs/ProcessWithdrawalJob.php))
- **Key Feature**: Asynchronous job queue processing
- Handles the actual payout processing via external providers
- **Retry Logic**: 
  - Max 3 attempts (configurable)
  - 60-second backoff between retries
- **Event Dispatching**:
  - On success: Dispatches `WithdrawalSucceeded` event → triggers `FinalizeWithdrawalListener`
  - On final failure: Dispatches `WithdrawalFailed` event → triggers fund release
- **Provider Response**: Stores provider reference for tracking

### 4. **Updated ProcessWithdrawalAction** ([ProcessWithdrawalAction.php](Modules/Wallet/actions/ProcessWithdrawalAction.php))
- Now dispatches `ProcessWithdrawalJob` immediately after status → `PROCESSING`
- Maintains idempotency through existing withdrawal logic
- Non-blocking: Returns PROCESSING status while job runs asynchronously

### 5. **Enhanced WithdrawalRequest Validation** ([WithdrawalRequest.php](Modules/Wallet/app/Http/Requests/WithdrawalRequest.php))
- Added conditional validation for destination fields
- Three destination types supported:
  - **bank_account**: requires `account_number` and `bank_code`
  - **mobile_money**: requires `phone_number` and `provider`
  - **wallet**: requires `wallet_address`
- Prevents invalid payouts at the API level

### 6. **Service Provider Registration** ([WalletServiceProvider.php](Modules/Wallet/Providers/WalletServiceProvider.php))
- Binds `PayoutProvider` interface to `StubPayoutProvider`
- Ready for production provider swap: update binding to use real provider class

### 7. **Comprehensive Tests** ([ProcessWithdrawalJobTest.php](Modules/Wallet/tests/Feature/Jobs/ProcessWithdrawalJobTest.php))
- ✅ Test 1: Job dispatch verification
- ✅ Test 2: Successful payout handling
- ✅ Test 3: Retry logic on provider failure

## Complete Withdrawal Flow

```
1. API Call: POST /user/withdrawals
   ↓
2. Validation: Amount, currency, destination details
   ↓
3. InitiateWithdrawalAction
   ├─ Lock user wallet
   ├─ Check idempotency
   ├─ Reserve funds (RESERVE transaction)
   ├─ Create Withdrawal (PENDING status)
   └─ Call ProcessWithdrawalAction
      ↓
4. ProcessWithdrawalAction
   ├─ Update status to PROCESSING
   └─ Dispatch ProcessWithdrawalJob
      ↓
5. ProcessWithdrawalJob (Async - Queue)
   ├─ Fetch withdrawal
   ├─ Call PayoutProvider.execute()
   │
   ├─ On Success:
   │  ├─ Store provider_reference
   │  └─ Dispatch WithdrawalSucceeded event
   │     ↓
   │     FinalizeWithdrawalListener
   │     ├─ Finalize RESERVE transaction
   │     └─ Create DEBIT transaction
   │        ↓
   │        Withdrawal marked SUCCESS
   │
   └─ On Failure:
      ├─ If retries < maxAttempts: Release job for retry
      └─ If retries = maxAttempts: Dispatch WithdrawalFailed event
         ↓
         ReleaseReservedFundsListener
         ├─ Create RESERVE_RELEASE transaction
         └─ Withdrawal marked FAILED
```

## Test Results

All 25 Wallet tests passing (84 assertions):
- ✅ Deposit flow tests
- ✅ Withdrawal actions (Initiate, Process, Finalize, Fail)
- ✅ New ProcessWithdrawalJob tests
- ✅ Reconciliation jobs
- ✅ Transfer tests

## Production Implementation Checklist

- [ ] Replace `StubPayoutProvider` with actual provider implementation
- [ ] Implement provider-specific error handling
- [ ] Add webhook handlers for provider callbacks
- [ ] Configure queue driver (Redis, SQS, database, etc.)
- [ ] Set up monitoring for failed jobs
- [ ] Test end-to-end with real payment gateway sandbox
- [ ] Add logging for payout operations
- [ ] Consider rate limiting for payout requests
- [ ] Set up alerts for withdrawal failures

## Files Created/Modified

### Created:
- `Modules/Wallet/app/Interfaces/PayoutProvider.php`
- `Modules/Wallet/app/Providers/StubPayoutProvider.php`
- `Modules/Wallet/app/Jobs/ProcessWithdrawalJob.php`
- `Modules/Wallet/tests/Feature/Jobs/ProcessWithdrawalJobTest.php`

### Modified:
- `Modules/Wallet/actions/ProcessWithdrawalAction.php` - Now dispatches job
- `Modules/Wallet/app/Http/Requests/WithdrawalRequest.php` - Enhanced validation
- `Modules/Wallet/Providers/WalletServiceProvider.php` - Registered PayoutProvider
