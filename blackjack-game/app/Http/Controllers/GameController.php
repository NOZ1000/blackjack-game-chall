<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GameService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;

use function PHPUnit\Framework\isEmpty;

class GameController extends Controller
{
    // POST: /game
    public function createGame()
    {
        $uuid = \Illuminate\Support\Str::uuid();
        $gameService = new GameService();

        Cache::put($uuid, $gameService->serializeState(), now()->addMinutes(10));

        return response()->json([
            'message' => 'Game created successfully.',
            'uuid' => $uuid,
        ]);
    }

    // POST: /game/{uuid}/bet
    public function placeBet(Request $request, $uuid)
    {
        $amount = $request->input('amount');

        if (!$amount || $amount < 10000) {
            return response()->json(['error' => 'Minimum bet is 10,000.'], 400);
        }

        $gameService = $this->getGameService($uuid);

        try {
            $gameService->placeBet($amount);

            Cache::put($uuid, $gameService->serializeState(), now()->addMinutes(10));

            return response()->json([
                'message' => 'Bet placed successfully. Amount: ' . $amount,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // POST: /game/{uuid}/hit
    public function hit($uuid)
    {
        $gameService = $this->getGameService($uuid);

        if ($gameService->currentBet === 0) {
            return response()->json([
                'error' => 'You need to bet something.'
            ], 400);
        }

        if ($gameService->isRoundFinished) {
            return response()->json([
                'error' => 'Game is finished.'
            ], 400);
        }

        try {
            $gameService->playerHit();

            $result = $gameService->getPlayerState()['state'] === 'busted'
                ? $gameService->determineWinner()
                : null;

            Cache::put($uuid, $gameService->serializeState(), now()->addMinutes(10));

            return response()->json([
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // POST: /game/{uuid}/stand
    public function stand($uuid)
    {
        $gameService = $this->getGameService($uuid);

        if ($gameService->getPlayerState()['currentBet'] === 0) {
            return response()->json([
                'error' => 'Game not started yet.'
            ], 400);
        }

        if ($gameService->isRoundFinished) {
            return response()->json([
                'message' => 'Game is finished.'
            ], 400);
        }

        try {
            $gameService->playerStand();

            $winner = $gameService->determineWinner();

            Cache::put($uuid, $gameService->serializeState(), now()->addMinutes(10));

            return response()->json([
                'result' => $winner
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    // POST: /game/{uuid}/surrender
    public function surrender($uuid)
    {
        $gameService = $this->getGameService($uuid);

        if ($gameService->getPlayerState()['currentBet'] === 0) {
            return response()->json([
                'error' => 'Game not started yet.'
            ], 400);
        }

        if ($gameService->isRoundFinished) {
            return response()->json([
                'message' => 'Game is finished.'
            ], 400);
        }

        try {
            $gameService->playerSurrender();

            Cache::put($uuid, $gameService->serializeState(), now()->addMinutes(1));

            return response()->json([
                'result' => 'dealer'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getGameStatus($uuid)
    {
        $gameService = $this->getGameService($uuid);

        $dealerHand = $gameService->isRoundFinished
            ? $gameService->getDealerState()['hand']
            : [
                ...$gameService->getDealerState()['hand'][0],
                'hidden'
            ];

        return response()->json([
            'playerState' => $gameService->getPlayerState(),
            'dealerState' => [
                'hand' => $dealerHand,
                'score' => $gameService->getDealerState()['score'],
                'state' => $gameService->getDealerState()['state'],
            ],
            'isRoundFinished' => $gameService->isRoundFinished,
        ]);
    }


    protected function getGameService($uuid)
    {
        $serializedState = Cache::get($uuid);

        if (!$serializedState) {
            abort(404, 'Game not found.');
        }

        $gameService = new GameService();
        $gameService->restoreState($serializedState);

        return $gameService;
    }

    public function exportGameSession($uuid)
    {
        $gameService = $this->getGameService($uuid);

        try {
            $gameSessionData = $gameService->serializeState();

            $encryptionKey = env('AES_ENCRYPTION_KEY');

            if (!$encryptionKey) {
                throw new \Exception('Encryption key is not set in the environment variables.');
            }

            $iv = random_bytes(openssl_cipher_iv_length('aes-128-cbc'));

            $encryptedContent = openssl_encrypt(
                $gameSessionData,
                'aes-128-cbc',
                $encryptionKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encryptedContent === false) {
                throw new \Exception('Failed to encrypt game session.');
            }

            $encryptedData = base64_encode($encryptedContent);
            $encodedIv = base64_encode($iv);

            return response()->json([
                'message' => 'Game session exported successfully.',
                'encryptedSession' => $encryptedData,
                'iv' => $encodedIv,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export game session: ' . $e->getMessage()], 500);
        }
    }


    public function decryptAndRestoreSession(Request $request)
    {
        try {
            $request->validate([
                'encryptedSession' => 'required|string',
                'iv' => 'required|string',
            ]);

            $encryptedSession = base64_decode($request->input('encryptedSession'));
            $iv = base64_decode($request->input('iv'));
            $newUuid = \Illuminate\Support\Str::uuid();


            $encryptionKey = env('AES_ENCRYPTION_KEY');
            if (!$encryptionKey) {
                throw new \Exception('Encryption key is not set in the environment variables.');
            }

            $decryptedContent = openssl_decrypt(
                $encryptedSession,
                'aes-128-cbc',
                $encryptionKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decryptedContent === false) {
                throw new \Exception('Failed to decrypt the game session.');
            }


            $gameService = new GameService();
            $gameService->restoreState($decryptedContent);

            Cache::put($newUuid, $gameService->serializeState(), now()->addMinutes(10));

            return response()->json([
                'message' => 'Game session restored successfully.',
                'newUuid' => $newUuid,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to restore game session: ' . $e->getMessage()], 500);
        }
    }

    public function getFlag($uuid)
    {
        $gameService = $this->getGameService($uuid);

        if (!($gameService->getPlayerState()['money'] >= 777777)) {
            return response()->json([
                'error' => 'Not enough money to get flag. You need to have more than $777,777.'
            ], 400);
        }

        $flag = env('FLAG');

        return response()->json([
            'message' => 'Congratulations! ' . $flag
        ]);
    }
}
