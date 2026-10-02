# Documentation API - Moov Money Universe

## Base URL

```
http://localhost:8000/api
```

## Authentification

L'API utilise Laravel Sanctum pour l'authentification basée sur des tokens.

### Obtenir un token

**Endpoint:** `POST /login`

**Request:**
```json
{
  "email": "admin@moov.tg",
  "password": "password"
}
```

**Response:**
```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "Admin Moov",
    "email": "admin@moov.tg",
    "role": {
      "id": 1,
      "name": "admin",
      "display_name": "Administrateur Moov Money"
    },
    "organization": null
  }
}
```

### Utiliser le token

Ajoutez le token dans les headers de chaque requête:

```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxx
```

## Endpoints

### Authentification

#### Login
```http
POST /login
Content-Type: application/json

{
  "email": "string",
  "password": "string"
}
```

#### Logout
```http
POST /logout
Authorization: Bearer {token}
```

#### Get Current User
```http
GET /me
Authorization: Bearer {token}
```

---

### Points de Vente (PDV)

#### Lister les PDV
```http
GET /point-of-sales?status=pending&region=MARITIME&page=1&per_page=15
Authorization: Bearer {token}
```

**Query Parameters:**
- `status`: pending | validated | rejected
- `region`: MARITIME | PLATEAUX | CENTRALE | KARA | SAVANES
- `prefecture`: string
- `organization_id`: integer
- `search`: string
- `page`: integer
- `per_page`: integer (max 100)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "dealer_name": "Dealer ABC",
      "numero_flooz": "90123456",
      "nom_point": "Boutique Centre Ville",
      "region": "MARITIME",
      "prefecture": "Golfe",
      "commune": "Lomé",
      "latitude": 6.1319,
      "longitude": 1.2228,
      "status": "pending",
      "created_at": "2025-12-02T10:00:00.000000Z",
      "organization": {...},
      "creator": {...}
    }
  ],
  "current_page": 1,
  "per_page": 15,
  "total": 50
}
```

#### Créer un PDV
```http
POST /point-of-sales
Authorization: Bearer {token}
Content-Type: application/json

{
  "dealer_name": "Dealer ABC",
  "numero_flooz": "90123456",
  "shortcode": "ABC001",
  "nom_point": "Boutique Centre Ville",
  "profil": "Détaillant",
  "type_activite": "Commerce général",
  "firstname": "Jean",
  "lastname": "Dupont",
  "date_of_birth": "1990-01-01",
  "gender": "M",
  "id_description": "CNI",
  "id_number": "1234567890",
  "id_expiry_date": "2030-01-01",
  "nationality": "Togolaise",
  "profession": "Commerçant",
  "region": "MARITIME",
  "prefecture": "Golfe",
  "commune": "Lomé",
  "ville": "Lomé",
  "quartier": "Hédzranawoé",
  "localisation": "Près du marché",
  "latitude": 6.1319,
  "longitude": 1.2228,
  "gps_accuracy": 15.5,
  "numero_proprietaire": "90111111",
  "autre_contact": "90222222",
  "nif": "TG123456",
  "regime_fiscal": "Impôt Synthétique",
  "support_visibilite": "Enseigne",
  "etat_support": "BON"
}
```

**Response:**
```json
{
  "pdv": {...},
  "proximity_alert": {
    "has_nearby": true,
    "nearby_pdvs": [
      {
        "id": 5,
        "nom_point": "Boutique Voisine",
        "distance": 150.5
      }
    ],
    "alert_distance": 300,
    "count": 1
  }
}
```

#### Afficher un PDV
```http
GET /point-of-sales/{id}
Authorization: Bearer {token}
```

#### Modifier un PDV
```http
PUT /point-of-sales/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "nom_point": "Nouveau nom",
  ...
}
```

**Note:** Seuls les PDV avec status "pending" peuvent être modifiés.

#### Supprimer un PDV
```http
DELETE /point-of-sales/{id}
Authorization: Bearer {token}
```

#### Valider un PDV (Admin uniquement)
```http
POST /point-of-sales/{id}/validate
Authorization: Bearer {token}
```

#### Rejeter un PDV (Admin uniquement)
```http
POST /point-of-sales/{id}/reject
Authorization: Bearer {token}
Content-Type: application/json

{
  "rejection_reason": "Coordonnées GPS incorrectes"
}
```

#### Vérifier la proximité
```http
POST /point-of-sales/check-proximity
Authorization: Bearer {token}
Content-Type: application/json

{
  "latitude": 6.1319,
  "longitude": 1.2228,
  "exclude_id": 1
}
```

**Response:**
```json
{
  "has_nearby": true,
  "nearby_pdvs": [
    {
      "id": 5,
      "nom_point": "Boutique Voisine",
      "dealer_name": "Dealer XYZ",
      "distance": 150.5
    }
  ],
  "alert_distance": 300,
  "count": 1
}
```

---

### Géographie

#### Lister les régions
```http
GET /geography/regions
Authorization: Bearer {token}
```

**Response:**
```json
[
  "MARITIME",
  "PLATEAUX",
  "CENTRALE",
  "KARA",
  "SAVANES"
]
```

#### Lister les préfectures d'une région
```http
GET /geography/prefectures?region=MARITIME
Authorization: Bearer {token}
```

**Response:**
```json
[
  "Golfe",
  "Agoè_Nyivé",
  "Lacs",
  "Vo",
  "Yoto",
  "Zio",
  "Bas_Mono",
  "Avé"
]
```

#### Lister les communes d'une préfecture
```http
GET /geography/communes?prefecture=Golfe
Authorization: Bearer {token}
```

#### Lister les cantons d'une commune
```http
GET /geography/cantons?commune=Lomé
Authorization: Bearer {token}
```

#### Obtenir toute la hiérarchie
```http
GET /geography/hierarchy
Authorization: Bearer {token}
```

**Response:**
```json
{
  "MARITIME": {
    "Golfe": ["Lomé", "Agoè-Nyivé"],
    "Lacs": ["Aného"],
    ...
  },
  "PLATEAUX": {...},
  ...
}
```

---

### Organisations

#### Lister les organisations (Admin uniquement)
```http
GET /organizations?search=dealer&is_active=1
Authorization: Bearer {token}
```

#### Créer une organisation (Admin uniquement)
```http
POST /organizations
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Dealer ABC",
  "code": "ABC001",
  "phone": "+22890111111",
  "email": "contact@dealer-abc.tg",
  "address": "123 Rue du Commerce, Lomé",
  "is_active": true
}
```

#### Afficher une organisation
```http
GET /organizations/{id}
Authorization: Bearer {token}
```

#### Modifier une organisation (Admin uniquement)
```http
PUT /organizations/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Nouveau nom",
  "is_active": false
}
```

#### Supprimer une organisation (Admin uniquement)
```http
DELETE /organizations/{id}
Authorization: Bearer {token}
```

---

### Statistiques

#### Dashboard
```http
GET /statistics/dashboard
Authorization: Bearer {token}
```

**Response:**
```json
{
  "stats": {
    "total": 150,
    "pending": 20,
    "validated": 120,
    "rejected": 10
  },
  "by_region": [
    {
      "region": "MARITIME",
      "count": 80
    },
    ...
  ],
  "by_organization": [
    {
      "id": 1,
      "name": "Dealer ABC",
      "point_of_sales_count": 45
    },
    ...
  ],
  "recent_pdvs": [...]
}
```

#### Statistiques par région
```http
GET /statistics/by-region
Authorization: Bearer {token}
```

**Response:**
```json
{
  "MARITIME": [
    {
      "region": "MARITIME",
      "status": "validated",
      "count": 50
    },
    {
      "region": "MARITIME",
      "status": "pending",
      "count": 10
    }
  ],
  ...
}
```

#### Statistiques par organisation (Admin uniquement)
```http
GET /statistics/by-organization
Authorization: Bearer {token}
```

#### Timeline
```http
GET /statistics/timeline?days=30
Authorization: Bearer {token}
```

**Response:**
```json
{
  "2025-12-01": [
    {
      "date": "2025-12-01",
      "status": "validated",
      "count": 5
    },
    {
      "date": "2025-12-01",
      "status": "pending",
      "count": 2
    }
  ],
  ...
}
```

---

### Export

#### Export XML
```http
GET /export/xml?organization_id=1&region=MARITIME&date_from=2025-01-01&date_to=2025-12-31
Authorization: Bearer {token}
```

**Response:** Fichier XML

```xml
<?xml version="1.0" encoding="UTF-8"?>
<pointsOfSale>
  <pdv>
    <id>1</id>
    <numero>1</numero>
    <status>validated</status>
    <dealer>
      <name>Dealer ABC</name>
      <numero_flooz>90123456</numero_flooz>
      ...
    </dealer>
    ...
  </pdv>
  ...
</pointsOfSale>
```

#### Export CSV
```http
GET /export/csv
Authorization: Bearer {token}
```

**Response:** Fichier CSV

```csv
ID,Numero,Dealer,Numero Flooz,Nom Point,Region,Prefecture,Commune,Latitude,Longitude,Status,Created At
1,1,Dealer ABC,90123456,Boutique Centre,MARITIME,Golfe,Lomé,6.1319,1.2228,validated,2025-12-02 10:00:00
...
```

---

## Codes d'Erreur

### 200 - OK
Requête réussie

### 201 - Created
Ressource créée avec succès

### 400 - Bad Request
Données de requête invalides

### 401 - Unauthorized
Token manquant ou invalide

### 403 - Forbidden
Accès refusé (permissions insuffisantes)

### 404 - Not Found
Ressource non trouvée

### 422 - Unprocessable Entity
Erreur de validation

**Response:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "latitude": [
      "The latitude must be between -90 and 90."
    ]
  }
}
```

### 500 - Internal Server Error
Erreur serveur

---

## Permissions par Rôle

### Admin
- Accès complet à toutes les ressources
- Peut voir tous les PDV
- Peut valider/rejeter les PDV
- Peut gérer les organisations
- Accès aux statistiques globales

### Dealer
- Peut voir uniquement les PDV de son organisation
- Peut créer des PDV
- Peut gérer les utilisateurs de son organisation
- Accès aux statistiques de son organisation

### Commercial
- Peut créer des PDV pour son organisation
- Peut voir les PDV qu'il a créés
- Peut modifier les PDV en attente qu'il a créés

---

## Exemples d'utilisation

### Exemple avec cURL

```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@moov.tg","password":"password"}'

# Lister les PDV (avec token)
curl -X GET http://localhost:8000/api/point-of-sales \
  -H "Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxx"

# Créer un PDV
curl -X POST http://localhost:8000/api/point-of-sales \
  -H "Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "dealer_name": "Test Dealer",
    "numero_flooz": "90123456",
    "nom_point": "Test PDV",
    "firstname": "John",
    "lastname": "Doe",
    "region": "MARITIME",
    "prefecture": "Golfe",
    "latitude": 6.1319,
    "longitude": 1.2228
  }'
```

### Exemple avec JavaScript/Axios

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Login
const { data } = await api.post('/login', {
  email: 'admin@moov.tg',
  password: 'password',
});

// Set token for future requests
api.defaults.headers.Authorization = `Bearer ${data.token}`;

// Get PDVs
const pdvs = await api.get('/point-of-sales', {
  params: {
    status: 'pending',
    region: 'MARITIME',
  },
});

console.log(pdvs.data);
```

---

## Rate Limiting

L'API n'a actuellement pas de limitation de taux (rate limiting) configurée. Cela peut être ajouté dans une version future.

## Versioning

L'API actuelle est en version 1 (v1). Les futures versions majeures seront accessibles via un préfixe de version (ex: `/api/v2/`).
