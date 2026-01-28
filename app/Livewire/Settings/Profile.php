<?php

namespace App\Livewire\Settings;

use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class Profile extends Component
{
    use WithFileUploads;

    public $last_name = '';
    public $first_name = '';
    public $email = '';
    public $id_card_number = '';
    public $tax_id = '';
    public $ssn = '';
    public $address = '';
    public $phone = '';

    public $signature;
    public $signatureData;
    public $currentSignature;

    // Avatar
    public $avatar;

    public function mount()
    {
        $user = auth()->user();
        
        $this->last_name = $user->last_name;
        $this->first_name = $user->first_name;
        $this->email = $user->email;
        
        $this->id_card_number = $user->id_card_number;
        $this->tax_id = $user->tax_id;
        $this->ssn = $user->ssn;
        $this->address = $user->address;
        $this->phone = $user->phone;
        
        $this->currentSignature = $user->signature_path;
    }

    public function save()
    {
        $user = auth()->user();

        $validated = $this->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'id_card_number' => ['nullable', 'string', 'max:20'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'ssn' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        Flux::toast(__('Profile updated successfully.'), variant: 'success');
    }
    
    public function saveAvatar()
    {
        $this->validate([
            'avatar' => 'required|image|max:1024', // 1MB
        ]);

        $user = auth()->user();

        $user->addMedia($this->avatar->getRealPath())
            ->toMediaCollection('avatar');

        $this->reset('avatar');

        Flux::toast(__('Profile picture saved successfully.'), variant: 'success');
        $this->dispatch('avatar-updated');
    }

    public function deleteAvatar()
    {
        auth()->user()->clearMediaCollection('avatar');

        Flux::toast(__('Profile picture deleted.'), variant: 'success');
        $this->dispatch('avatar-updated');
    }
    
    public function saveSignatureUpload()
    {
        $this->validate([
            'signature' => 'required|image|max:1024', // 1MB
        ]);

        $user = auth()->user();
        
        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $path = $this->signature->store('signatures', 'public');
        $user->update(['signature_path' => $path]);
        
        $this->currentSignature = $path;
        $this->reset('signature');
        
        Flux::toast(__('Signature saved successfully.'), variant: 'success');
    }
    
    public function saveSignatureDraw()
    {
        $this->validate([
            'signatureData' => 'required|string',
        ]);
        
        $user = auth()->user();
        
        // Base64 decode
        $image = str_replace('data:image/png;base64,', '', $this->signatureData);
        $image = str_replace(' ', '+', $image);
        $imageName = 'signatures/' . $user->id . '_' . time() . '.png';
        
        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
        }
        
        Storage::disk('public')->put($imageName, base64_decode($image));
        $user->update(['signature_path' => $imageName]);
        
        $this->currentSignature = $imageName;
        $this->reset('signatureData');
        
        Flux::toast(__('Signature saved successfully.'), variant: 'success');
    }
    
    public function deleteSignature()
    {
        $user = auth()->user();
        
        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
            $user->update(['signature_path' => null]);
        }
        
        $this->currentSignature = null;
        Flux::toast(__('Signature deleted.'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.settings.profile');
    }
}
