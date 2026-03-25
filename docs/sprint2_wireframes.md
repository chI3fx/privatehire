# Sprint 2 Wireframes (Low Fidelity)

## 1) Customer Online Booking Form (`/booking/book.php`)

```text
+--------------------------------------------------------------------------------+
| PrivateHire Cars                                  [Book Ride] [My Bookings]    |
+--------------------------------------------------------------------------------+
| BOOK TAXI                                                                       |
|--------------------------------------------------------------------------------|
| Pickup Location:        [______________________________________________]       |
| Destination:            [______________________________________________]       |
| Journey Date:           [____-__-__]   Journey Time: [__:__]                  |
| Passengers:             [__]                                                |
| Vehicle Size:           [v Select vehicle size (name + seats)]               |
| Notification Preference:[v SMS | Email | Both]                                |
|                                                                                |
|                            [ Continue to Summary ]                             |
+--------------------------------------------------------------------------------+
```

## 2) Booking Summary + Payment (`/booking/book.php` step 2)

```text
+--------------------------------------------------------------------------------+
| BOOKING SUMMARY                                                                 |
|--------------------------------------------------------------------------------|
| Pickup: ......................... Westlands                                     |
| Destination: .................... Kileleshwa                                    |
| Date/Time: ...................... 2026-03-24 16:30                              |
| Vehicle: ........................ Mercedes Vito                                 |
| Total Cost: ..................... 120.00                                       |
| Online Discount (15%): .......... -18.00                                       |
| Final Cost: ..................... 102.00                                       |
|--------------------------------------------------------------------------------|
| Payment Method:         [v PayPal | Card]                                       |
| Card Type:              [v VISA | Mastercard | Amex]                           |
| Card Number:            [______________________________________________]       |
|                                                                                |
|                            [ Confirm & Pay ]                                   |
+--------------------------------------------------------------------------------+
```

## 3) Customer Bookings + Cancellation (`/booking/my_bookings.php`, `/booking/cancel.php`)

```text
+---------------------------------------------------------------------------------------------------+
| MY BOOKINGS                                                                                       |
|---------------------------------------------------------------------------------------------------|
| Pickup | Destination | Date | Time | Driver | Channel | Final Cost | Payment | Status | Actions |
|---------------------------------------------------------------------------------------------------|
| ...    | ...         | ...  | ...  | ...    | online  | 102.00     | paid    | Booked | Receipt |
|                                                                                           [Cancel]|
+---------------------------------------------------------------------------------------------------+

Cancellation screen:
+--------------------------------------------------------------------------------+
| Cancel Booking #123                                                            |
|--------------------------------------------------------------------------------|
| Journey: Westlands -> Kileleshwa, 2026-03-24 16:30                            |
| Refund Policy: Cancellations allowed >= 24h before pickup.                    |
| If paid and eligible, status is marked refunded.                               |
|                                                                                |
|                 [ Confirm Cancellation ]   [ Back ]                            |
+--------------------------------------------------------------------------------+
```

## 4) Printable Receipt (`/booking/receipt.php`)

```text
+--------------------------------------------------------------------------------+
| Booking Receipt                                                    [Print]      |
|--------------------------------------------------------------------------------|
| Journey Details: Booking ID, Customer, Channel, Pickup, Destination, Status    |
| Driver & Vehicle: Driver name/phone, reg number, car details                   |
| Payment: Total, Discount %, Discount amount, Final cost, Method, Ref, Card     |
+--------------------------------------------------------------------------------+
```

## 5) Call Centre Booking (`/admin/call_bookings.php`)

```text
+--------------------------------------------------------------------------------------------------+
| CALL CENTRE BOOKING                                                                               |
|--------------------------------------------------------------------------------------------------|
| Search Customer (name/phone/email): [__________________________________] [Search]               |
|--------------------------------------------------------------------------------------------------|
| Results: Name | Email | Phone | [Select]                                                         |
|--------------------------------------------------------------------------------------------------|
| Selected Customer: ...                                                                             |
| Masked Card History: VISA **** **** **** 1234                                                    |
|                                                                                                   |
| [ ] I have verified caller identity                                                               |
| Pickup [________]  Destination [________]  Date [____-__-__] Time [__:__]                        |
| Passengers [__]  Vehicle [v]  Notification [v]  Payment Method [v]                               |
|                                                                                                   |
|                                [ Create Phone Booking ]                                           |
+--------------------------------------------------------------------------------------------------+
```

## 6) Admin Dashboard On-Route Action (`/admin/dashboard.php`)

```text
Recent Bookings Table includes:
Customer | Pickup | Destination | Date | Time | Vehicle | Driver | Channel | Status | Actions

Actions:
- If status = Booked -> [Mark On Route]
- On click: status updates, driver details notification sent (SMS, fallback email)
```
