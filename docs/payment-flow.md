# Integration Guide - PIX Payment API

This document outlines the complete flow for processing PIX payments using our API.

## 1. User Authentication

To access the payment endpoint, the user must be authenticated in the system. The authentication process works as follows:

- Endpoint: `/api/login`
- Method: `POST`
- Content-Type: `application/json`

### Request Payload
```json
{
  "email": "your-email@example.com",
  "password": "your-password"
}
```

If the credentials are valid, a JWT token will be returned:

```json
{
  "success": true,
  "message": "Token generated successfully",
  "token": "YOUR_JWT_TOKEN",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

Include the token in the `Authorization` header for all subsequent requests:
```
Authorization: Bearer YOUR_JWT_TOKEN
```

---

## 2. Required Data for PIX Payment

To create a PIX payment, the client must send a JSON payload with the following fields:

```json
{
  "externalId": "AAXNTMTYTFJZC1KSYMMYNMY2SYSZTK4ZO2", 
  "amount": 10,
  "document": "05294567962",
  "name": "LUCIANO VARGAS",
  "identification": "010202",
  "expire": 3600,
  "description": "TESTE"
}
```

> ⚠️ All fields are required.

---

## 3. Sending the Request

- Endpoint: `/create-payment`
- Method: `POST`
- Content-Type: `application/json`
- Header: `Authorization: Bearer YOUR_JWT_TOKEN`

---

## 4. API Response

After processing the request, the API will respond with a JSON object:

### Success Response Example
```json
{
  "success": true,
  "message": "Payment processed successfully",
  "data": {
    "pix": "00020126850014br.gov.bcb.pix2563pix.voluti.com.br/...",
    "uuid": "943c8da5-f771-4a10-b5a1-f4678d65ca4c",
    "externalId": "012553856872857548828558",
    "amount": "12.00",
    "createdAt": "2025-04-25T23:05:42.180Z",
    "expire": 3600
  }
}
```

### Error Response Example
```json
{
  "success": false,
  "message": "Payment processing failed",
  "data": {
    "error": "Invalid document number"
  }
}
```

---

## Important Notes

- The generated QR code is valid for the time defined by the `expire` field.
- All payment attempts are logged automatically for auditing purposes.
- In case of failure, check the submitted data and try again.
- Monetary values must be sent in decimal format without symbols or separators (e.g., `10.50` for R$10.50).
