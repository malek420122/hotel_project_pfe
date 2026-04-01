<h2>{{ $subjectLine }}</h2>
<p>Bonjour {{ $firstName }},</p>
<p>{{ $messageBody }}</p>
@if($discountCode)
<p>Code promo: <strong>{{ $discountCode }}</strong></p>
@endif
<p>A bientot sur HotelEase.</p>
