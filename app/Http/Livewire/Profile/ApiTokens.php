<?php

namespace App\Http\Livewire\Profile;

use Illuminate\Http\Request;
use Livewire\Component;
use Laravel\Jetstream\ConfirmsPasswords;

class ApiTokens extends Component
{
    use ConfirmsPasswords;
    public $creatingToken = false;
    public $confirmDeleteToken = false;
    public $confirmRevokeAll = false;
    public $showToken = false;
    public $plainTextToken = null;
    public $newTokenName = '';
    public $tokens = null;

    protected $rules = [
      'newTokenName' => 'required|alpha_dash|min:6'
    ];

    public function mount(){
      $this->tokens = auth()->user()->tokens;
    }

    public function newToken(){
      $this->creatingToken = true;
    }

    public function createToken(){
      $this->validate();
      $token = auth()->user()->createToken($this->newTokenName);
      $this->plainTextToken = $token->plainTextToken;
      $this->tokens = auth()->user()->tokens;
      $this->showToken = true;
      $this->reset(['creatingToken','newTokenName']);
    }

    public function clearToken(){
      $this->showToken = false;
      $this->plainTextToken = null;
    }

    public function confirmDeleteTokenId($tokenId){
      $this->confirmDeleteToken = $tokenId;
    }

    public function deleteToken(){
      auth()->user()->tokens()->where('id', $this->confirmDeleteToken)->delete();
      $this->tokens = $this->tokens->except($this->confirmDeleteToken);
      $this->confirmDeleteToken = false;
    }

    public function confirmingRevokeAll(){
      $this->ensurePasswordIsConfirmed();
      $this->confirmRevokeAll = true;
    }

    public function revokeAllTokens(){
      auth()->user()->tokens()->delete();
      $this->reset(['tokens','confirmRevokeAll']);
    }

    public function render(){
        return view('livewire.profile.api-tokens');
    }
}
