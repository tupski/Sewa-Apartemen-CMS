# Booking System

## Overview

The booking system is a lightweight lead capture and WhatsApp handoff system. It captures customer inquiries and converts them into WhatsApp conversations for follow-up. This approach keeps the system simple and doesn't require complex payment processing.

## Booking Philosophy

### What Booking Is

- ✅ Lead capture
- ✅ Customer contact information
- ✅ Date range selection
- ✅ WhatsApp handoff
- ✅ Booking database record
- ✅ WhatsApp message generation

### What Booking Is Not

- ❌ Payment processing
- ❌ Reservation engine
- ❌ Calendar management
- ❌ Automatic confirmation
- ❌ Email notifications (use WhatsApp instead)
- ❌ Complex workflows

### Business Model

```
Customer Interest
      ↓
Customer clicks "Tanya" or "Booking"
      ↓
Customer fills form (name, phone, dates, guests)
      ↓
Booking record created in database
      ↓
WhatsApp message generated with booking details
      ↓
Customer redirected to WhatsApp
      ↓
Staff responds via WhatsApp
      ↓
Booking confirmed/cancelled in database
```

## Booking Flow

### Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    Customer View                                │
│                    (Property Detail)                            │
└────────────────────┬────────────────────────────────────────────┘
                     │
                     ↓
         ┌──────────────────────────────┐
         │   Choose Action              │
         │   (Tanya OR Booking)         │
         └────────────┬─────────────────┘
                      │
         ┌────────────┴────────────┐
         │                         │
    Tanya Button             Booking Button
         │                         │
         ↓                         ↓
┌──────────────────┐    ┌──────────────────────┐
│ WhatsApp Direct  │    │ Booking Form Modal   │
│ Link             │    │ (Inline Form)        │
└────────┬─────────┘    └──────────┬───────────┘
         │                         │
         └─────────┬───────────────┘
                   ↓
         ┌──────────────────────────┐
         │   WhatsApp Redirect      │
         │   (No Form Required)     │
         └──────────────────────────┘
```

### Booking Button Flow

```
User Clicks "Booking"
      ↓
Booking Modal Opens (Alpine.js)
      ↓
Form Displays (Name, Phone, Dates, Guests)
      ↓
User Fills Form
      ↓
Form Submits via AJAX
      ↓
Validation (Backend)
      ↓
Booking Created
      ↓
Booking Code Generated
      ↓
WhatsApp Message Generated
      ↓
WhatsApp Number Determined
      ↓
Redirect to WhatsApp
```

## Booking Components

### 1. WhatsApp Button (Tanya)

```html
<!-- Simple WhatsApp button -->
<a href="{{ $whatsappUrl }}" class="btn btn-primary">
    <svg class="icon-whatsapp" />
    <span>Tanya</span>
</a>

<!-- WhatsApp URL Structure -->
https://wa.me/{phone}?text={message}

<!-- Prefilled Message -->
Halo [Website Name] 👋

Saya ingin bertanya mengenai:

Property: [Property Name]
Unit: [Unit Name]

Apakah unit ini masih tersedia?
```

### 2. Booking Modal

```html
<!-- Booking Modal (Alpine.js) -->
<div x-data="bookingForm()" x-show="showModal" x-cloak>
    <!-- Overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50" @click="close"></div>
    
    <!-- Modal Content -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            
            <!-- Header -->
            <div class="p-6 border-b">
                <h3 class="text-xl font-semibold">Book {{ $unit->name }}</h3>
                <p class="text-gray-500">Fill in your details to inquire about availability</p>
            </div>
            
            <!-- Form -->
            <form @submit.prevent="submit">
                <!-- Property Info -->
                <div class="p-6 bg-gray-50">
                    <p class="text-sm font-medium">Property:</p>
                    <p class="text-lg font-semibold">{{ $unit->property->name }}</p>
                    <p class="text-sm font-medium">Unit:</p>
                    <p class="text-lg font-semibold">{{ $unit->name }}</p>
                </div>
                
                <!-- Form Fields -->
                <div class="p-6 space-y-4">
                    
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium">Name *</label>
                        <input type="text" 
                               x-model="form.name"
                               class="form-input mt-1"
                               required>
                    </div>
                    
                    <!-- WhatsApp Phone -->
                    <div>
                        <label class="block text-sm font-medium">WhatsApp Number *</label>
                        <input type="tel" 
                               x-model="form.phone"
                               placeholder="6281234567890"
                               class="form-input mt-1"
                               required>
                    </div>
                    
                    <!-- Email (Optional) -->
                    <div>
                        <label class="block text-sm font-medium">Email (Optional)</label>
                        <input type="email" 
                               x-model="form.email"
                               class="form-input mt-1">
                    </div>
                    
                    <!-- Check-in Date -->
                    <div>
                        <label class="block text-sm font-medium">Check-in Date *</label>
                        <input type="date" 
                               x-model="form.check_in"
                               :min="today"
                               class="form-input mt-1"
                               required>
                    </div>
                    
                    <!-- Check-out Date -->
                    <div>
                        <label class="block text-sm font-medium">Check-out Date *</label>
                        <input type="date" 
                               x-model="form.check_out"
                               :min="form.check_in"
                               class="form-input mt-1"
                               required>
                    </div>
                    
                    <!-- Guests -->
                    <div>
                        <label class="block text-sm font-medium">Number of Guests *</label>
                        <input type="number" 
                               x-model="form.guests"
                               :min="1"
                               :max="unit.max_guests"
                               class="form-input mt-1"
                               required>
                    </div>
                    
                    <!-- Notes (Optional) -->
                    <div>
                        <label class="block text-sm font-medium">Additional Notes (Optional)</label>
                        <textarea x-model="form.notes"
                                  rows="3"
                                  class="form-input mt-1"></textarea>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="p-6 border-t flex justify-end space-x-3">
                    <button type="button" 
                            @click="close"
                            class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="loading"
                            class="btn btn-primary">
                        <span x-show="!loading">Send Inquiry</span>
                        <span x-show="loading" class="spinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function bookingForm() {
    return {
        showModal: false,
        loading: false,
        today: new Date().toISOString().split('T')[0],
        unit: @js($unit),
        form: {
            name: '',
            phone: '',
            email: '',
            check_in: '',
            check_out: '',
            guests: 1,
            notes: ''
        },
        
        open() {
            this.showModal = true;
            this.form.check_in = '';
            this.form.check_out = '';
            this.form.guests = 1;
        },
        
        close() {
            this.showModal = false;
        },
        
        async submit() {
            this.loading = true;
            
            try {
                const response = await fetch('/bookings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.form)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirect to WhatsApp
                    window.location.href = data.whatsapp_url;
                } else {
                    alert(data.message || 'Something went wrong');
                }
            } catch (error) {
                alert('Network error. Please try again.');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
```

## Booking Database

### Booking Table Schema

```php
Schema::create('bookings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('property_id')->constrained()->onDelete('cascade');
    $table->foreignId('unit_id')->constrained()->onDelete('cascade');
    $table->string('booking_code')->unique();
    $table->string('name');
    $table->string('phone');
    $table->string('email')->nullable();
    $table->date('check_in');
    $table->date('check_out');
    $table->unsignedTinyInteger('guests');
    $table->text('notes')->nullable();
    $table->enum('status', ['new', 'contacted', 'confirmed', 'completed', 'cancelled', 'spam'])->default('new');
    $table->string('landing_page')->nullable();
    $table->string('utm_source')->nullable();
    $table->string('utm_medium')->nullable();
    $table::string('utm_campaign')->nullable();
    $table->string('utm_term')->nullable();
    $table->string('utm_content')->nullable();
    $table->string('whatsapp_number');
    $table->string('whatsapp_url');
    $table->timestamps();
});
```

### Booking Model

```php
class Booking extends Model
{
    protected $fillable = [
        'property_id',
        'unit_id',
        'booking_code',
        'name',
        'phone',
        'email',
        'check_in',
        'check_out',
        'guests',
        'notes',
        'status',
        'landing_page',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'whatsapp_number',
        'whatsapp_url',
    ];
    
    protected $casts = [
        'guests' => 'integer',
        'check_in' => 'date',
        'check_out' => 'date',
    ];
    
    // Relations
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
    
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    
    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }
    
    public function scopeContacted($query)
    {
        return $query->where('status', 'contacted');
    }
    
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }
}
```

## Booking Service

### Booking Service Class

```php
class BookingService
{
    public function create(array $data): Booking
    {
        // Generate booking code
        $bookingCode = $this->generateCode();
        
        // Determine WhatsApp number (Unit → Property → Default)
        $whatsappNumber = $this->getWhatsAppNumber($data['unit_id']);
        
        // Generate WhatsApp message
        $message = $this->generateWhatsAppMessage($data, $bookingCode, $whatsappNumber);
        
        // Generate WhatsApp URL
        $whatsappUrl = "https://wa.me/{$whatsappNumber}?text=" . urlencode($message);
        
        // Create booking
        return Booking::create([
            'property_id' => $data['property_id'],
            'unit_id' => $data['unit_id'],
            'booking_code' => $bookingCode,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'guests' => $data['guests'],
            'notes' => $data['notes'] ?? null,
            'status' => 'new',
            'landing_page' => $data['landing_page'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_term' => $data['utm_term'] ?? null,
            'utm_content' => $data['utm_content'] ?? null,
            'whatsapp_number' => $whatsappNumber,
            'whatsapp_url' => $whatsappUrl,
        ]);
    }
    
    protected function generateCode(): string
    {
        $date = now()->format('Ymd');
        $sequence = Booking::whereDate('created_at', today())->count() + 1;
        
        return 'BK-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    protected function getWhatsAppNumber(int $unitId): string
    {
        // Try unit WhatsApp first
        $unit = Unit::find($unitId);
        if ($unit && $unit->whatsapp_number) {
            return $unit->whatsapp_number;
        }
        
        // Try property WhatsApp
        $property = Property::find($unit?->property_id);
        if ($property && $property->whatsapp_number) {
            return $property->whatsapp_number;
        }
        
        // Use default WhatsApp
        return settings('whatsapp_default');
    }
    
    protected function generateWhatsAppMessage(array $data, string $bookingCode, string $whatsappNumber): string
    {
        $unit = Unit::find($data['unit_id']);
        $property = Property::find($data['property_id']);
        
        $message = "Halo! 👋\n\n";
        $message = "Saya ingin bertanya mengenai unit ini:\n";
        $message .= "Property: {$property->name}\n";
        $message .= "Unit: {$unit->name}\n\n";
        $message .= " Detail inquiry:\n";
        $message .= "Booking Code: {$bookingCode}\n";
        $message .= "Nama: {$data['name']}\n";
        $message .= "No WhatsApp: {$data['phone']}\n";
        $message .= "Email: " . ($data['email'] ?? '-') . "\n";
        $message .= "Check-in: {$data['check_in']}\n";
        $message .= "Check-out: {$data['check_out']}\n";
        $message .= "Jumlah Tamu: {$data['guests']}\n";
        
        if (!empty($data['notes'])) {
            $message .= "Catatan: {$data['notes']}\n";
        }
        
        return $message;
    }
}
```

## WhatsApp Integration

### WhatsApp URL Structure

```text
https://wa.me/{phone_number}?text={message}
```

### Example WhatsApp URLs

```php
// Simple WhatsApp link
https://wa.me/6281234567890?text=Halo,%20saya%20ingin%20bertanya

// URL encoded message
https://wa.me/6281234567890?text=Halo%20{Website}%20👋%0A%0ASaya%20ingin%20bertanya%20mengenai%20unit%20ini%3A%0AProperty%3A%20{PropertyName}%0AUnit%3A%20{UnitName}
```

### WhatsApp Number Fallback

```php
// Priority: Unit → Property → Default
public function getWhatsAppNumber(int $unitId): string
{
    // Unit WhatsApp
    $unit = Unit::find($unitId);
    if ($unit->whatsapp_number) {
        return $unit->whatsapp_number;
    }
    
    // Property WhatsApp
    $property = Property::find($unit->property_id);
    if ($property->whatsapp_number) {
        return $property->whatsapp_number;
    }
    
    // Default WhatsApp
    return settings('whatsapp_default');
}
```

### WhatsApp Validation

```php
// Validate WhatsApp number format
public function validateWhatsAppNumber(string $number): bool
{
    // Remove all non-numeric characters except +
    $cleanNumber = preg_replace('/[^0-9+]/', '', $number);
    
    // Must start with + or country code
    if (!preg_match('/^\+?[0-9]{8,15}$/', $cleanNumber)) {
        return false;
    }
    
    return true;
}
```

## Admin Booking Management

### Booking List

```php
class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['property', 'unit']);
        
        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        // Filter by property
        if ($request->property_id) {
            $query->where('property_id', $request->property_id);
        }
        
        // Filter by date range
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        
        $bookings = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.bookings.index', compact('bookings'));
    }
    
    public function show(Booking $booking)
    {
        $booking->load(['property', 'unit']);
        return view('admin.bookings.show', compact('booking'));
    }
    
    public function update(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'required|in:new,contacted,confirmed,completed,cancelled,spam',
        ]);
        
        $booking->update([
            'status' => $request->status,
        ]);
        
        return back()->with('success', 'Booking status updated.');
    }
}
```

### Booking Admin View

```html
<!-- resources/views/admin/bookings/index.blade.php -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Bookings</h2>
        
        <!-- Filters -->
        <div class="filter-bar">
            <select wire:model="statusFilter">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="confirmed">Confirmed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
                <option value="spam">Spam</option>
            </select>
            
            <input type="date" wire:model="fromDate" placeholder="From">
            <input type="date" wire:model="toDate" placeholder="To">
        </div>
    </div>
    
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Customer</th>
                    <th>Property</th>
                    <th>Unit</th>
                    <th>Check-in</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    <tr>
                        <td>{{ $booking->booking_code }}</td>
                        <td>
                            {{ $booking->name }}<br>
                            <small>{{ $booking->phone }}</small>
                        </td>
                        <td>{{ $booking->property->name }}</td>
                        <td>{{ $booking->unit->name }}</td>
                        <td>{{ $booking->check_in }}</td>
                        <td>
                            <span class="badge badge-{{ $booking->status }}">
                                {{ __($booking->status) }}
                            </span>
                        </td>
                        <td>{{ $booking->created_at->diffForHumans() }}</td>
                        <td>
                            <a href="{{ route('admin.bookings.show', $booking) }}" class="btn btn-sm">
                                View
                            </a>
                            
                            <!-- Status dropdown -->
                            <select wire:model="status"
                                    wire:change="updateStatus({{ $booking->id }}, $event.target.value)">
                                <option value="new" {{ $booking->status == 'new' ? 'selected' : '' }}>New</option>
                                <option value="contacted" {{ $booking->status == 'contacted' ? 'selected' : '' }}>Contacted</option>
                                <option value="confirmed" {{ $booking->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="completed" {{ $booking->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ $booking->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                <option value="spam" {{ $booking->status == 'spam' ? 'selected' : '' }}>Spam</option>
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Pagination -->
        {{ $bookings->links() }}
    </div>
</div>
```

## Analytics & Tracking

### Booking Events

```javascript
// Booking events for analytics
window.dataLayer = window.dataLayer || [];

// When booking form opens
function onBookingFormOpen() {
    window.dataLayer.push({
        event: 'booking_form_open',
        property_id: '{{ $property->id }}',
        unit_id: '{{ $unit->id }}',
    });
}

// When booking form submits
function onBookingSubmit(bookingCode) {
    window.dataLayer.push({
        event: 'booking_submit',
        property_id: '{{ $property->id }}',
        unit_id: '{{ $unit->id }}',
        booking_code: bookingCode,
    });
}

// When redirected to WhatsApp
function onWhatsAppRedirect() {
    window.dataLayer.push({
        event: 'whatsapp_redirect',
        property_id: '{{ $property->id }}',
        unit_id: '{{ $unit->id }}',
    });
}
```

### UTM Tracking

```php
// Capture UTM parameters
$request->session()->put([
    'utm_source' => $request->utm_source,
    'utm_medium' => $request->utm_medium,
    'utm_campaign' => $request->utm_campaign,
    'utm_term' => $request->utm_term,
    'utm_content' => $request->utm_content,
]);

// Store in booking
$booking->utm_source = $request->session()->get('utm_source');
$booking->utm_medium = $request->session()->get('utm_medium');
$booking->utm_campaign = $request->session()->get('utm_campaign');
```

### Admin Analytics

```php
// Booking statistics
$stats = [
    'total' => Booking::count(),
    'new' => Booking::new()->count(),
    'contacted' => Booking::contacted()->count(),
    'confirmed' => Booking::confirmed()->count(),
    'completed' => Booking::where('status', 'completed')->count(),
    'cancelled' => Booking::where('status', 'cancelled')->count(),
    'spam' => Booking::where('status', 'spam')->count(),
];

// Today's bookings
$todayBookings = Booking::whereDate('created_at', today())->count();

// Recent bookings
$recentBookings = Booking::with(['property', 'unit'])
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();
```

## Booking Validation

### Booking Request Validation

```php
class StoreBookingRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[0-9+]{8,20}$/',
            'email' => 'nullable|email|max:255',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after_or_equal:check_in',
            'guests' => 'required|integer|min:1|max:99',
            'notes' => 'nullable|string|max:1000',
        ];
    }
    
    public function messages()
    {
        return [
            'phone.regex' => 'Please enter a valid phone number (8-20 digits)',
            'check_in.after_or_equal' => 'Check-in date must be today or in the future',
            'check_out.after_or_equal' => 'Check-out date must be after or equal to check-in date',
            'guests.min' => 'At least 1 guest required',
            'guests.max' => 'Maximum 99 guests allowed',
        ];
    }
}
```

### Business Rules

```php
// Check-in must be before check-out
if ($checkIn >= $checkOut) {
    return response()->json([
        'success' => false,
        'message' => 'Check-out date must be after check-in date',
    ]);
}

// Check-in must be in the future
if ($checkIn < now()->startOfDay()) {
    return response()->json([
        'success' => false,
        'message' => 'Check-in date must be today or in the future',
    ]);
}

// Maximum guests validation
if ($guests > $unit->max_guests) {
    return response()->json([
        'success' => false,
        'message' => 'Exceeds maximum guests for this unit',
    ]);
}
```

## Booking Confirmation (Optional)

### Manual Confirmation via WhatsApp

```
Customer: Tanya mengenai unit Studio Deluxe
Staff: Apakah masih tersedia untuk 10-15 Agustus?
Customer: Ya
Staff: Terima kasih, booking kami catat. Silakan transfer deposit sejumlah Rp 2.000.000
Customer: Sudah transfer
Staff: Terima kasih, booking anda konfirmasi.see you!
```

### Admin Dashboard

```php
// Booking status changes
- New (baru masuk)
- Contacted (sudah dikontak)
- Confirmed (sudah dikonfirmasi)
- Completed (sudah selesai)
- Cancelled (dibatalkan)
- Spam (spam/invalid)
```

## Booking Email (Optional)

```php
// Send confirmation email (optional)
Mail::to($booking->email)->send(new BookingConfirmation($booking));
```

## Conclusion

The booking system provides:

- ✅ Simple lead capture
- ✅ WhatsApp handoff
- ✅ Booking database record
- ✅ Booking code generation
- ✅ WhatsApp message generation
- ✅ WhatsApp number fallback
- ✅ UTM tracking
- ✅ Admin management
- ✅ Status tracking

The system is designed for simplicity and effectiveness, focusing on converting inquiries into WhatsApp conversations rather than complex reservation workflows.