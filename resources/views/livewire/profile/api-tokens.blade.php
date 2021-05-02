<x-jet-action-section>
    <x-slot name="title">
        {{ __('API Tokens') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Create and revoke Personal Access tokens.') }}
    </x-slot>

    <x-slot name="content">
      <p class="border-b pb-3 mb-3">
        Tokens generated can be used to access the API
      </p>
      @if(count($tokens) > 0)
          @foreach($tokens as $token)
          <div class="flex flex-row py-2">
            <div class="flex-auto">
              <a href="/user/profile/tokens/{{ $token->id }}" class="text-blue-700">
                {{ $token->name }}
              </a>
              <em class="text-sm">
                —
                @if(count($token->abilities) > 0 && $token->abilities[0] != "*")
                  @foreach($token->abilities as $ability)
                    {{ $ability }}
                  @endforeach
                @else
                  Full access
                @endif
              </em>
            </div>
            <div class="flex-none text-right">
              <span class="text-sm mx-3">
                {{ $token->last_used_at ?? 'Never used' }}
              </span>
              <button class="text-red-600 text-xs py-1 px-2 border rounded hover:bg-gray-50" wire:click="confirmDeleteTokenId({{ $token->id }})" wire:loading.attr="disabled">
                Delete
              </button>
            </div>
          </div>
          @endforeach
      @endif
        <div class="flex items-center mt-5">
            <x-jet-button wire:click="$toggle('creatingToken')" wire:loading.attr="disabled">
                {{ __('New Token') }}
            </x-jet-button>
            @if(count($tokens) > 0)
              <x-jet-confirms-password wire:then="confirmingRevokeAll">
                <x-jet-danger-button wire:loading.attr="disabled" class="px-3 py-2 ml-3">
                    Revoke All
                </x-jet-danger-button>
              </x-jet-confirms-password>

            @endif
        </div>

        <!-- New token Modal -->
        <x-jet-dialog-modal wire:model="creatingToken">
            <x-slot name="title">
                {{ __('Create A New Personal Access Token') }}
            </x-slot>

            <x-slot name="content">
                Enter a name to identify the new token.

                <div class="mt-4" x-data="{}" x-on:confirming-logout-other-browser-sessions.window="setTimeout(() => $refs.newTokenName.focus(), 250)">
                    <x-jet-input type="text" class="mt-1 block w-3/4" placeholder="{{ __('Token name') }}"
                                x-ref="newTokenName"
                                wire:model.defer="newTokenName"
                                wire:keydown.enter="createToken" />

                    <x-jet-input-error for="newTokenName" class="mt-2" />
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-jet-secondary-button wire:click="$toggle('creatingToken')" wire:loading.attr="disabled">
                    {{ __('Nevermind') }}
                </x-jet-secondary-button>

                <x-jet-button class="ml-2" wire:click="createToken" wire:loading.attr="disabled">
                    {{ __('Create Token') }}
                </x-jet-button>
            </x-slot>
        </x-jet-dialog-modal>

        <!-- Show new plaintext token modal -->
        <x-jet-dialog-modal wire:model="showToken">
            <x-slot name="title">
                {{ __('New Personal Access Token Created!') }}
            </x-slot>

            <x-slot name="content">
                Make sure you copy this token as needed right now because you will never get to see this again!

                <div class="m-6 text-center text-red-600">
                    <code class="font-mono">{{ $this->plainTextToken }}</code>
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-jet-button wire:click="clearToken" wire:loading.attr="disabled">
                    {{ __('Got It!') }}
                </x-jet-button>
            </x-slot>
        </x-jet-dialog-modal>

        <!-- Delete token Modal -->
        <x-jet-dialog-modal wire:model="confirmDeleteToken">
            <x-slot name="title">
                {{ __('Are you sure that you want to delete this access token?') }}
            </x-slot>

            <x-slot name="content">
              <p class="text-yellow-500 text-sm p-3 rounded border border-yellow-500 bg-yellow-50">
                Any applications or scripts using this token will no longer be able to access the API.<br>
                <b>You cannot undo this action.</b>
              </p>
            </x-slot>

            <x-slot name="footer">
                <x-jet-secondary-button wire:click="$toggle('confirmDeleteToken')" wire:loading.attr="disabled">
                    {{ __('Nevermind') }}
                </x-jet-secondary-button>

                <x-jet-danger-button class="ml-2" wire:click="deleteToken" wire:loading.attr="disabled">
                    {{ __('Yes, delete the token') }}
                </x-jet-danger-button>
            </x-slot>
        </x-jet-dialog-modal>

        <!-- Revoke all tokens Modal -->
        <x-jet-dialog-modal wire:model="confirmRevokeAll">
            <x-slot name="title">
                {{ __('Are you sure you want to revoke access for all personal access tokens?') }}
            </x-slot>

            <x-slot name="content">
              <p class="text-yellow-500 text-sm p-3 rounded border border-yellow-500 bg-yellow-50">
                This will revoke access for all personal access tokens.<br>
                <b>This action cannot be undone.</b>
              </p>
            </x-slot>

            <x-slot name="footer">
                <x-jet-secondary-button wire:click="$toggle('confirmRevokeAll')" wire:loading.attr="disabled">
                    {{ __('Nevermind') }}
                </x-jet-secondary-button>

                <x-jet-danger-button class="ml-2" wire:click="revokeAllTokens" wire:loading.attr="disabled">
                    {{ __('Yes, revoke access for all tokens') }}
                </x-jet-danger-button>
            </x-slot>
        </x-jet-dialog-modal>


    </x-slot>
</x-jet-action-section>
