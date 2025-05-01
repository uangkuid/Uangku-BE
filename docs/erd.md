 ```mermaid
erDiagram
    cash_flows {
        id uuid PK
        name varchar
    }

    users {
        id uuid PK
        password varchar
        email varchar
        name varchar
        avatar varchar
        created_at timestamp
        updated_at timestamp
    }

    user_keys {
        id uuid PK
        users uuid FK
        public_key varchar
        private_key varchar
        hashed_key varchar
        hashed_pin varchar
    }

    user_configs {
        id uuid PK
        users uuid FK
        is_pin_enabled boolean
        start_date_month varchar
    }

    user_sessions {
        id uuid PK
        users uuid FK
        refresh_token varchar
    }

    family {
        id uuid PK
        name string
        avatar varchar
        created_by uuid
        created_at timestamp
        updated_at timestamp
    }

    family_member {
        id uuid PK
        user uuid FK
        family uuid FK
        role enum
        created_at timestamp
        updated_at timestamp
    }

    family_keys {
        id uuid PK
        family uuid FK
        public_key varchar
        private_key varchar
        hashed_key varchar
    }

    categories {
        id uuid PK
        cash_flows uuid FK
        name varchar
        created_at timestamp
        updated_at timestamp
    }

    sub_categories {
        id uuid PK
        categories uuid FK
        users uuid FK
        name string
        created_at timestamp
        updated_at timestamp
    }

    transactions {
        id uuid PK
        users uuid FK
        categories uuid FK
        sub_categories uuid FK
        cash_flows uuid FK
        wallets uuid FK
        families uuid FK
        amount double
        note text
        created_at timestamp
        updated_at timestamp
    }

    wallet {
        id uuid PK
        name varchar
        amount double
        families uuid FK
        created_by uuid FK
        created_at timestamp
        updated_at timestamp
    }

    wallet_access {
        id uuid PK
        users uuid FK
        wallet uuid FK
        isActive boolean
        role enum
    }

    wallet_transactions {
        id uuid PK
        wallets uuid FK
        accessId uuid FK
        amount double
        type enum
        created_at timestamp
        updated_at timestamp
    }

    cash_flows ||--o{ categories: has
    cash_flows ||--o{ transactions: has
    categories ||--o{ sub_categories: has
    categories ||--o{ transactions: has
    sub_categories ||--o{ transactions: has
    users ||--o| family_member: has
    users ||--o{ transactions: has
    users ||--o{ wallet_access: has
    users ||--o{ sub_categories: has
    users ||--o| user_keys: has
    users ||--o{ user_sessions: has
    users ||--o| user_configs: has
    family ||--o{ family_member: has
    family ||--o{ transactions: has
    family ||--o{ wallet: has
    family ||--o| family_keys: has
    wallet }|--o{ wallet_access: has
    wallet_access ||--o{ wallet_transactions: has
    wallet_transactions ||--o| transactions: has
    wallet ||--o{ wallet_transactions: has
```

## Table Details

### Users Table

Table to store user information.
This table contains the user's personal information, including their name, email, password, and avatar. The password is
hashed for security purposes.

| Field      | Type      | Index | Description                                                                                 |
|------------|-----------|-------|---------------------------------------------------------------------------------------------|
| id         | uuid      | PK    | Unique identifier for the user                                                              |
| name       | varchar   |       | Name of the user, encrypted using AES-CBC-256 key using Raw public key from table user_keys |
| email      | varchar   |       | Email address of the user, encrypted using AES-CBC-256 secret key system                    |
| password   | varchar   |       | Password for the user hashed using bcrypt                                                   |
| avatar     | varchar   |       | Avatar of the user                                                                          |
| created_at | timestamp |       | Timestamp when the user was created                                                         |
| updated_at | timestamp |       | Timestamp when the user was last updated                                                    |

### User Keys Table

Table to store user keys.
This table contains the user's public and private keys, which are used for encryption and decryption of sensitive data.
The private key is encrypted using AES-CBC-256 with a key derived from the user's secret key and password.

| Field       | Type    | Index | Description                                                                                                        |
|-------------|---------|-------|--------------------------------------------------------------------------------------------------------------------|
| id          | uuid    | PK    | Unique identifier for the user key                                                                                 |
| users       | uuid    | FK    | Foreign key referencing the users table                                                                            |
| public_key  | varchar |       | Public key of the user, encoded using base64                                                                       |
| private_key | varchar |       | Private key of the user, encrypted using AES-CBC-256 using key user secret_key + user passwords, encoded by base64 |
| hashed_key  | varchar |       | User Secret Key hash with bcrypt and salt                                                                          |
| hashed_pin  | varchar |       | User pin hash with bcrypt and salt                                                                                 |

### User Session Table

Table to store user sessions.
This table contains the refresh token for the user session, which is used for authentication and authorization purposes.

| Field         | Type    | Index | Description                             |
|---------------|---------|-------|-----------------------------------------|
| id            | uuid    | PK    | Unique identifier for the user session  |
| users         | uuid    | FK    | Foreign key referencing the users table |
| refresh_token | varchar |       | Refresh token for the user session      |

### User Config Table

Table to store user configuration.
This table contains the user's configuration settings, including whether the PIN is enabled or not.

| Field            | Type    | Index | Description                                                                                                            |
|------------------|---------|-------|------------------------------------------------------------------------------------------------------------------------|
| id               | uuid    | PK    | Unique identifier for the user configuration                                                                           |
| users            | uuid    | FK    | Foreign key referencing the users table                                                                                |
| is_pin_enabled   | boolean |       | Indicates whether the PIN is enabled for the user                                                                      |
| start_date_month | varchar |       | Start date month for the user configuration, encrypted using AES-CBC-256 key using Raw public key from table user_keys |

## Secret Keys

### Secret key only have 3 types:

- **Users Secret Key** : Secret key account binding
- **Families Secret Key** : Secret key family binding
- **System Secret Key** : Secret key system binding

## Secret Key Mapping

**Table** :

- **Users** : Secret key using System Secret Key
- **Wallets** : Secret key using creator secret key, when creator is user using users secret key when using families
  secret key will using family secret key
- **Family** : Secret key using System secret key
- **Wallet_Transactions** : Secret key using wallet creator secret key
- **Transactions** : Secret key using wallet creator secret key

## Encryption Algoritm:

- **Users Data** : Asymmetric encryption using RSA with key raw public key
- **Email** : Asymmetric encryption using AES with raw public key
