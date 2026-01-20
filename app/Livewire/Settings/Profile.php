<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Flux\Flux;

class Profile extends Component
{
    use ProfileValidationRules;
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    
    // Signature
    public $signature; // File upload
    public $signatureData; // Base64 from pad

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
        
        Flux::toast(__('Profile updated successfully.'), variant: 'success');
    }
    
    public function saveSignature()
    {
        $user = Auth::user();
        
        if ($this->signature) {
            $this->validate([
                'signature' => 'image|max:1024', // 1MB Max
            ]);
            
            // Töröljük a régit
            if ($user->signature_path) {
                Storage::disk('public')->delete($user->signature_path);
            }
            
            $path = $this->signature->store('signatures', 'public');
            $user->update(['signature_path' => $path]);
            
            $this->reset('signature');
        } elseif ($this->signatureData) {
            // Base64 mentése
            $image = str_replace('data:image/png;base64,', '', $this->signatureData);
            $image = str_replace(' ', '+', $image);
            $imageName = 'signatures/' . $user->id . '_' . time() . '.png';
            
            // Töröljük a régit
            if ($user->signature_path) {
                Storage::disk('public')->delete($user->signature_path);
            }
            
            Storage::disk('public')->put($imageName, base64_decode($image));
            $user->update(['signature_path' => $imageName]);
            
            $this->reset('signatureData');
        }
        
        Flux::toast(__('Signature saved successfully.'), variant: 'success');
    }
    
    public function deleteSignature()
    {
        $user = Auth::user();
        
        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
            $user->update(['signature_path' => null]);
            Flux::toast(__('Signature deleted.'), variant: 'success');
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

}
