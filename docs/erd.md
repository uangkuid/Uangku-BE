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

    family {
        id uuid PK
        name string
        avatar varchar
        shared_key varchar
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
    
    cash_flows ||--o{ categories : has
    cash_flows ||--o{ transactions : has

    categories ||--o{ sub_categories : has
    categories ||--o{ transactions : has

    sub_categories ||--o{ transactions : has

    users ||--o| family_member : has
    users ||--o{ transactions : has
    users ||--o{ wallet_access : has
    users ||--o{ sub_categories : has
    
    family ||--o| family_member : has
    family ||--o{ transactions : has
    family ||--o{ wallet : has

    wallet }|--o{ wallet_access : has
    wallet_access ||--o{ wallet_transactions : has
    wallet_transactions ||--o| transactions : has
    wallet ||--o{ wallet_transactions : has
```

## Secret Keys

Secret key only have 3 types:
- **Users Secret Key** : Secret key account binding
- **Families Secret Key** : Secret key family binding
- **System Secret Key** : Secret key system binding

Secret Key Algoritm:

## Secret Key Mapping
**Table** :
- **Users** : Secret key using System Secret Key
- **Wallets** : Secret key using creator secret key, when creator is user using users secret key when using families secret key will using family secret key
- **Family** : Secret key using System secret key
- **Wallet_Transactions** : Secret key using wallet creator secret key
- **Transactions** : Secret key using wallet creator secret key


