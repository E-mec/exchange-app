# Crypto Exchange API

`Crypto Exchange API` is a modular Laravel 12 backend for user onboarding, wallet management, internal transfers, deposit orchestration, payment gateway initialization, and webhook-driven wallet funding.

The codebase is organized around business domains instead of a single flat `app/` folder. Core features live inside Laravel modules:

- `Auth` handles registration, OTP verification, login, logout, profile fetch, and password reset.
- `Wallet` handles user wallets, balances, transfers, deposits, withdrawals, ledger entries, and reconciliation.
- `Payment` handles provider initialization, webhook verification, payment records, and deposit completion hooks.

The project already contains a useful domain foundation for an exchange-style backend:

- JWT-based API authentication
- OTP-driven account verification
- Automatic wallet creation per supported currency
- Ledger-style wallet transactions with hash chaining
- Idempotency support for wallet mutations
- Event-driven handoff between wallet and payment modules
- Provider adapters for multiple payment gateways
- Pest feature tests around key auth and wallet flows

## Table of Contents

- [Overview](#overview)
- [Architecture at a Glance](#architecture-at-a-glance)
- [Project Structure](#project-structure)
- [Modules](#modules)
- [How the System Flows](#how-the-system-flows)
- [API Surface](#api-surface)
- [Data Model](#data-model)
- [Tech Stack and Tooling](#tech-stack-and-tooling)
- [Getting Started](#getting-started)
- [Environment and Configuration](#environment-and-configuration)
- [Development Workflow](#development-workflow)
- [Testing](#testing)
- [Design Notes](#design-notes)
- [Known Gaps and Implementation Notes](#known-gaps-and-implementation-notes)

## Overview

This repository is best understood as a modular financial API with three connected concerns:

1. A user signs up, verifies ownership of an email address with OTP, and receives a JWT.
2. The wallet domain manages balances per currency, internal transfers, withdrawals, and wallet transaction history.
3. The payment domain initializes external checkout sessions and processes provider webhooks to complete deposit funding.

Although the repository is named like a crypto exchange backend, the current implementation is focused more on:

- account onboarding,
- fiat and crypto wallet bookkeeping,
- internal value movement,
- payment provider abstraction,
- webhook-driven wallet crediting.

It is a good base for extending into a fuller exchange platform with trading, order books, market data, KYC workflows, and admin operations.

## Architecture at a Glance

The application uses Laravel's standard bootstrap process, but the business logic is mostly placed inside `Modules/` using `nwidart/laravel-modules`.

### High-level layers

- `bootstrap/`, `config/`, `routes/`: Laravel framework setup
- `app/`: shared cross-module helpers, actions, enums, exceptions
- `Modules/Auth`: identity and authentication domain
- `Modules/Wallet`: wallet and ledger domain
- `Modules/Payment`: payment gateway and webhook domain
- `tests/` and `Modules/*/tests`: feature and action-level tests

### Architectural style

The project leans on a service/action-based pattern:

- Controllers stay thin
- Request classes validate input
- Actions encapsulate business operations
- Events coordinate cross-module workflows
- Listeners react to domain events
- Models persist state
- DTOs shape response payloads

### Runtime flow

At runtime the flow typically looks like this:

`HTTP request -> route -> form request validation -> controller -> action/service -> model/event -> JSON response`

For event-driven flows:

`action -> event -> listener -> downstream action/service -> persisted state change`

## Project Structure

```text
cryptoExchangeApi/
|-- app/
|   |-- Actions/
|   |-- Enums/
|   |-- Exceptions/
|   `-- Helpers/
|-- bootstrap/
|-- config/
|-- database/
|   |-- migrations/
|   `-- seeders/
|-- Modules/
|   |-- Auth/
|   |   |-- actions/
|   |   |-- app/Http/Controllers/
|   |   |-- app/Http/Requests/
|   |   |-- database/
|   |   |-- dtos/
|   |   |-- enums/
|   |   |-- Models/
|   |   |-- Providers/
|   |   |-- routes/
|   |   `-- tests/
|   |-- Wallet/
|   |   |-- actions/
|   |   |-- app/Events/
|   |   |-- app/Http/Controllers/
|   |   |-- app/Http/Requests/
|   |   |-- app/Jobs/
|   |   |-- app/Listeners/
|   |   |-- app/Interfaces/
|   |   |-- database/
|   |   |-- dto/
|   |   |-- enums/
|   |   |-- Models/
|   |   |-- Providers/
|   |   |-- routes/
|   |   `-- tests/
|   `-- Payment/
|       |-- actions/
|       |-- app/Events/
|       |-- app/Gateways/
|       |-- app/Http/Controllers/
|       |-- app/Http/Middleware/
|       |-- app/Interfaces/
|       |-- app/Listeners/
|       |-- app/Resolver/
|       |-- config/
|       |-- database/
|       |-- Enums/
|       |-- Models/
|       |-- Providers/
|       |-- routes/
|       `-- Webhooks/
|-- public/
|-- resources/
|-- routes/
|-- storage/
|-- tests/
|-- composer.json
|-- package.json
`-- README.md
```

### What each top-level area is for

- `app/Actions`: shared actions such as OTP send/verify
- `app/Helpers/responseHelpers.php`: shared JSON success and failure helpers
- `app/Exceptions/CustomException.php`: domain-friendly JSON exception rendering
- `Modules/*/actions`: use-case level business logic
- `Modules/*/Providers`: module registration, routes, events, config, migrations
- `Modules/*/routes/api.php`: module API routes mounted under `/api`
- `Modules/*/database/migrations`: module-owned tables
- `Modules/*/tests`: module-focused behavior tests

## Modules

### Auth Module

Purpose:

- register users
- send and verify OTP
- issue JWTs
- support login/logout/profile refresh
- trigger password reset flows

Important responsibilities:

- Creates a user record
- Sends OTP via Laravel notifications
- Verifies OTP from cache
- Issues JWT using `php-open-source-saver/jwt-auth`
- Exposes profile and token refresh endpoints

Important pieces:

- `Modules/Auth/app/Http/Controllers`
- `Modules/Auth/actions/CreateUserAction.php`
- `Modules/Auth/actions/VerifyUserAction.php`
- `Modules/Auth/actions/LoginAction.php`
- `Modules/Auth/Models/User.php`

Notable implementation details:

- Registration sends OTP immediately after account creation.
- OTP values are cached for 2 minutes.
- Verification returns a JWT token and user payload.
- Login requires a verified email.
- The user model implements `JWTSubject`.
- Profile pictures are handled through Spatie Media Library.

### Wallet Module

Purpose:

- maintain user wallets by currency
- record immutable-looking balance movements
- support deposits, transfers, and withdrawals
- handle reservation and release of funds
- reconcile failed withdrawals

Important responsibilities:

- Creates wallets for users across all supported currencies
- Locks wallet rows during balance mutations
- Applies idempotent ledger transactions
- Separates available and reserved balances
- Generates transaction checksums and chained hashes
- Coordinates transfers and withdrawals through multi-step actions

Important pieces:

- `Modules/Wallet/actions/ApplyTransactionOrchestratorAction.php`
- `Modules/Wallet/actions/TransferFundsAction.php`
- `Modules/Wallet/actions/InitiateWithdrawalAction.php`
- `Modules/Wallet/actions/ReleaseReservedFundsAction.php`
- `Modules/Wallet/actions/BalanceCalculatorAction.php`
- `Modules/Wallet/Models/Wallet.php`
- `Modules/Wallet/Models/WalletTransaction.php`
- `Modules/Wallet/Models/Withdrawal.php`

Notable implementation details:

- Wallet writes use `lockForUpdate()` to reduce race conditions.
- Transfers are modeled as `reserve -> credit -> debit`.
- Withdrawals start as reserved funds before final settlement.
- Reserve release is explicit and idempotent.
- Every wallet transaction stores checksum, previous hash, and current hash.

### Payment Module

Purpose:

- initialize external payments
- abstract gateway-specific behavior
- verify signed webhooks
- update payment records
- trigger wallet crediting after successful payment

Important responsibilities:

- Resolves payment gateways by provider enum
- Persists a local `payments` record before provider initialization
- Returns `checkout_url` to the caller
- Verifies incoming webhook signatures
- Marks payment records as successful or failed
- Emits domain events consumed by the wallet module

Important pieces:

- `Modules/Payment/actions/InitializePayment.php`
- `Modules/Payment/app/Resolver/PaymentGatewayResolver.php`
- `Modules/Payment/app/Resolver/WebhookVerifierResolver.php`
- `Modules/Payment/Services/WebhookService.php`
- `Modules/Payment/app/Gateways/*`
- `Modules/Payment/Webhooks/*`
- `Modules/Payment/Models/Payment.php`

Currently wired providers:

- Paystack
- Stripe
- Flutterwave
- PayPal
- Coinbase

Provider enum also includes `binance`, but the resolver and webhook verification flow are not fully wired for it yet.

## How the System Flows

### 1. User registration and verification flow

1. Client sends registration payload to `POST /api/auth/register`.
2. `RegisterRequest` validates fields such as email, username, password, phone number, and optional profile picture.
3. `CreateUserAction` creates the user.
4. `SendOtpAction` generates a 6-digit OTP and stores it in cache for 2 minutes.
5. `UserRegisteredEvent` is dispatched.
6. Wallet listener creates one wallet per supported currency.
7. Client submits OTP to `POST /api/auth/verify/otp`.
8. `VerifyUserAction` validates the OTP and returns a JWT token.

### Result

At the end of onboarding the user has:

- a persisted account
- a verified email
- a JWT token
- pre-created wallets for supported currencies

### 2. Authentication flow

1. Client logs in with email and password.
2. `LoginAction` validates credentials and verified-email status.
3. JWT is issued.
4. Protected endpoints use the `auth:api` middleware.

Protected wallet and payment routes therefore expect:

`Authorization: Bearer <token>`

### 3. Wallet creation flow

Wallets are not manually created through a public endpoint. Instead:

1. Registration dispatches `UserRegisteredEvent`.
2. `CreateWalletForUser` listens for that event.
3. The listener loops through every `CurrencyEnum` case.
4. A wallet record is created for each currency.

Supported wallet currencies in the current codebase:

- `NGN`
- `USD`
- `EUR`
- `BTC`

### 4. Deposit flow

There are two related deposit/payment paths in the repository.

### Wallet-first deposit path

1. Authenticated user calls `POST /api/user/initiate/deposit`.
2. `InitiateDepositAction` creates a `payment_intents` record.
3. `DepositInitiatedEvent` is dispatched.
4. `InitializePaymentListener` forwards the payload into the payment module.
5. `InitializePayment` creates a `payments` record and initializes the external provider.
6. Provider later sends a webhook to `/api/webhooks/{provider}`.
7. Webhook verification runs.
8. `WebhookService` updates payment status.
9. On success, `PaymentSuccessful` triggers wallet crediting.

### Payment-direct initialization path

1. Authenticated user calls `POST /api/payments/initialize`.
2. `InitializePayment` creates a local payment record.
3. Gateway adapter returns a checkout URL.
4. Client redirects user to provider checkout.
5. Webhook confirms final status later.

### Deposit completion

When a payment is confirmed:

- payment status becomes `successful`
- wallet is located by `user_id + currency`
- `CreditWalletAction` writes a ledger credit
- wallet available balance increases

### 5. Transfer flow

Internal wallet transfer is designed as a safe multi-step mutation:

1. Sender wallet is loaded and locked.
2. Recipient wallet is loaded and locked.
3. Funds are reserved from the sender.
4. Recipient receives a finalized credit.
5. Sender reserve is converted into a finalized debit.

This is important because it gives the system:

- balance reservation semantics
- rollback safety through database transactions
- idempotency via request-supplied UUIDs
- clearer audit history in `wallet_transactions`

### 6. Withdrawal flow

1. Authenticated user calls `POST /api/user/withdrawals`.
2. Wallet is located by currency.
3. Funds are reserved first.
4. A `withdrawals` record is created with `pending` status.
5. Downstream processing can move it to `processing`.
6. Final success debits the reserved amount permanently.
7. Final failure releases the reserved amount back to available balance.

### Reconciliation flow

The `ReconcileWithdrawalsJob` scans failed withdrawals that are not yet reconciled and releases any leftover reserved balances using an idempotent release key.

## API Surface

All module API routes are mounted under the `/api` prefix.

### Auth endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/api/auth/register` | Create a user and send registration OTP |
| POST | `/api/auth/verify/otp` | Verify registration OTP and issue JWT |
| POST | `/api/auth/login` | Authenticate a verified user |
| POST | `/api/auth/logout` | Invalidate JWT |
| GET | `/api/auth/fetch/profile` | Return authenticated user profile |
| GET | `/api/auth/refresh` | Refresh JWT |
| POST | `/api/auth/password/otp` | Request password reset OTP |
| POST | `/api/auth/password/otp/verify` | Verify password reset OTP |
| POST | `/api/auth/password/reset` | Reset password |
| POST | `/api/resend/otp` | Resend OTP |

### Wallet endpoints

These routes are protected by `auth:api`.

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/api/user/initiate/deposit` | Start deposit intent flow |
| GET | `/api/user/wallets` | List current user's wallets |
| GET | `/api/user/wallets/{currency}` | Show wallet by currency |
| POST | `/api/user/transfer/funds` | Transfer funds to another user |
| POST | `/api/user/withdrawals` | Create withdrawal request |
| GET | `/api/user/transactions` | Declared route for transaction listing |
| GET | `/api/user/withdrawals` | Declared route for withdrawal listing |

### Payment endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/api/payments/initialize` | Initialize external checkout |
| POST | `/api/webhooks/{provider}` | Receive signed payment webhook |

### Request expectations at a glance

### Register

Expected fields include:

- `firstname`
- `lastname`
- `username`
- `email`
- `password`
- `password_confirmation`
- `phone_number`
- `dial_code`
- `country`
- optional `pin`
- optional `referred_by`
- optional `profile_picture`

### Wallet transfer

Expected fields:

- `from_currency`
- `to_currency`
- `amount`
- `to_user_id`
- `idempotency_key` as UUID

### Withdrawal

Expected fields:

- `amount`
- `currency`
- `destination`
- `idempotency_key` as UUID

### Payment initialize

Expected fields:

- `provider`
- `amount`
- `currency`
- `email`

## Data Model

The core tables visible from the migrations and models are:

### Shared and framework tables

- `users`
- `password_reset_tokens`
- `sessions`
- `jobs`
- `job_batches`
- `failed_jobs`
- `cache`
- `cache_locks`
- `media`
- Spatie permission tables

### Wallet domain tables

- `wallets`
- `wallet_transactions`
- `payment_intents`
- `withdrawals`

### Payment domain tables

- `payments`

### Core model responsibilities

### `users`

Stores identity, contact, password, optional PIN, referral data, verification timestamps, and media-linked profile pictures.

### `wallets`

Stores one wallet per user per currency with:

- `available_balance`
- `reserved_balance`
- `ledger_balance`
- `status`
- optional metadata

### `wallet_transactions`

Stores wallet ledger entries including:

- transaction type
- amount
- balance before and after
- reference
- idempotency key
- metadata
- checksum
- previous hash
- current hash
- finalization state

### `payment_intents`

Stores deposit initiation intent before or alongside external gateway processing.

### `payments`

Stores the local lifecycle of a provider payment:

- internal reference
- provider
- amount
- currency
- local status
- provider reference
- provider metadata

### `withdrawals`

Stores withdrawal lifecycle state:

- pending
- processing
- success
- failed
- reconciliation metadata

## Tech Stack and Tooling

### Backend

- PHP `^8.2`
- Laravel `^12`
- `nwidart/laravel-modules`
- `php-open-source-saver/jwt-auth`
- `spatie/laravel-data`
- `spatie/laravel-medialibrary`
- `spatie/laravel-permission`
- `guzzlehttp/guzzle`
- `stripe/stripe-php`

### Frontend and asset tooling

- Vite
- Tailwind CSS 4
- Axios
- Concurrently

The frontend footprint is currently light. This repository is primarily an API/backend project, with minimal Blade and asset scaffolding retained from Laravel/module defaults.

### Testing and quality tools

- Pest
- PHPUnit
- Mockery
- Laravel Pint

## Getting Started

### Prerequisites

You will typically want:

- PHP 8.2+
- Composer
- Node.js 18+ and npm
- a database supported by Laravel

The checked-in `.env.example` defaults to SQLite, which is a convenient local starting point.

### Installation

1. Install PHP dependencies:

```bash
composer install
```

2. Install frontend dependencies:

```bash
npm install
```

3. Create environment file:

```bash
cp .env.example .env
```

On Windows PowerShell, use:

```powershell
Copy-Item .env.example .env
```

4. Generate the application key:

```bash
php artisan key:generate
```

5. Generate JWT secret:

```bash
php artisan jwt:secret
```

6. Run migrations:

```bash
php artisan migrate
```

7. Build frontend assets:

```bash
npm run build
```

8. Start local development:

```bash
composer run dev
```

The `dev` script starts:

- Laravel development server
- queue listener
- log tailing via `pail`
- Vite dev server

### One-command setup

The project already defines a Composer helper:

```bash
composer run setup
```

This performs:

- dependency installation
- `.env` bootstrap
- app key generation
- migrations
- npm install
- asset build

## Environment and Configuration

### Base Laravel environment

The repository ships with standard Laravel environment entries for:

- app name and URL
- database
- session
- cache
- queue
- mail
- AWS
- Vite

### Important project-specific configuration

You will likely need to add values that are not present in `.env.example` but are required by the codebase.

### JWT

Required for API authentication:

- `JWT_SECRET`
- optionally `JWT_TTL`
- optionally `JWT_REFRESH_TTL`

### Payment gateways

Depending on provider usage:

- `PAYSTACK_SECRET_KEY`
- `PAYSTACK_PUBLIC_KEY`
- `PAYSTACK_WEBHOOK_SECRET`
- `FLUTTERWAVE_SECRET_KEY`
- `FLUTTERWAVE_PUBLIC_KEY`
- `FLUTTERWAVE_WEBHOOK_SECRET`
- `STRIPE_SECRET_KEY`
- `STRIPE_WEBHOOK_SECRET`
- `PAYPAL_CLIENT_ID`
- `PAYPAL_SECRET`
- `PAYPAL_WEBHOOK_ID`
- `PAYPAL_MODE`
- `COINBASE_API_KEY`
- `COINBASE_WEBHOOK_SECRET`

### Mail

OTP and notifications depend on Laravel notifications and mail configuration. In local development the default mailer is `log`, which is helpful for testing without a real SMTP provider.

### Queue

Queue-backed workflows and reconciliation work best when a queue worker is running. The project defaults to the `database` queue connection.

## Development Workflow

### Useful commands

```bash
composer run dev
composer run test
npm run dev
npm run build
php artisan migrate
php artisan queue:listen
```

### Module-centric development

When adding a feature, the intended project style is usually:

1. add or update the module route
2. validate input with a request class
3. keep controller thin
4. place the business rule in an action
5. dispatch an event if another module should react
6. add or update tests inside the module

This keeps cross-domain responsibilities separated.

## Testing

The repository includes both root-level Laravel tests and module-level Pest tests.

Covered areas include:

- registration and OTP verification
- login and JWT flows
- password reset controller behavior
- wallet transfer success, rollback, and idempotency
- withdrawal initiation and failure flows
- withdrawal reconciliation job behavior

Run the suite with:

```bash
composer run test
```

or

```bash
php artisan test
```

## Design Notes

### 1. Modularity first

The main design choice in this repository is domain modularity. Instead of mixing auth, wallet, and payment logic into one shared app layer, each domain owns its routes, requests, providers, migrations, tests, and most of its business logic.

### 2. Financial safety patterns

The wallet implementation shows several patterns commonly used in money movement systems:

- row-level locking
- explicit reserve and release semantics
- idempotency keys
- transaction chaining and hashing
- two-step settlement patterns

These are strong building blocks for avoiding double-spend and replay issues.

### 3. Event-driven boundaries

Cross-domain handoffs are done with events and listeners rather than tightly coupling wallet controllers to payment gateway code. That makes it easier to:

- swap providers
- queue operations later
- add notifications
- add audit hooks
- extend domain reactions cleanly

## Known Gaps and Implementation Notes

This README reflects the code as it exists today, including a few incomplete or mismatched areas worth knowing before extending the project.

- `GET /api/user/transactions` is declared in routes, but `WalletController` does not currently implement a `transactions()` method.
- `GET /api/user/withdrawals` is declared in routes, but `WithdrawalController` does not currently implement an `index()` method.
- `PaymentProviderEnum` includes `binance`, but gateway resolution and webhook verification are not fully wired for it.
- Wallet deposit validation currently accepts `bank`, but the payment initializer resolves only configured provider enums, so `bank` is not fully supported end-to-end in the current implementation.
- The payment module supports Stripe and Coinbase adapters, while the wallet deposit request currently validates only `paystack`, `flutterwave`, `bank`, and `paypal`.
- `.env.example` does not currently include the JWT and payment gateway secrets used by the codebase, so they need to be added manually.
- `DatabaseSeeder` still reflects starter-project scaffolding and may need cleanup before using it in a real environment.

## Summary

This project is a solid modular Laravel API foundation for:

- user onboarding
- JWT authentication
- wallet bookkeeping
- internal transfers
- withdrawal lifecycle management
- external payment initialization
- webhook-driven wallet crediting

If you are extending it, the next most natural areas to improve are:

- complete route/controller parity
- add API documentation or OpenAPI specs
- formalize provider support matrix
- expand queue-based async processing
- add admin and reconciliation dashboards
- harden observability, auditing, and monitoring
