<?php

namespace App\Services;

class GameService
{
    protected $playerHand;
    protected $dealerHand;
    protected $playerState;
    protected $dealerState;
    protected $playerMoney;
    public $currentBet;
    public $isRoundFinished;

    public function __construct()
    {
        $this->playerHand = [];
        $this->dealerHand = [];
        $this->playerState = 'playing';
        $this->dealerState = 'playing';
        $this->playerMoney = 100000;
        $this->currentBet = 0;
        $this->isRoundFinished = false;
    }

    public function initializeGame()
    {
        if ($this->currentBet < 10000) {
            throw new \Exception('Minimum bet is 10,000.');
        }
    }

    public function placeBet($amount)
    {
        if ($amount < 10000) {
            throw new \Exception('Minimum bet is 10,000.');
        }

        if ($amount > $this->playerMoney) {
            throw new \Exception('Not enough money to place this bet.');
        }

        $this->playerState = 'playing';
        $this->isRoundFinished = false;
        $this->currentBet = $amount;
        $this->playerMoney -= $amount;

        $this->dealerHand = $this->generateDealerHand();
        $this->playerHand = $this->generatePlayerHand($this->dealerHand);
    }


    public function serializeState()
    {
        return serialize([
            'm' => $this->playerMoney,
            'pH' => $this->playerHand,
            'dH' => $this->dealerHand,
            'pS' => $this->playerState,
            'dS' => $this->dealerState,
            'cB' => $this->currentBet,
            'iRF' => $this->isRoundFinished
        ]);
    }

    public function restoreState($serializedData)
    {
        $state = unserialize($serializedData);
        $this->playerMoney = $state['m'];
        $this->playerHand = $state['pH'];
        $this->dealerHand = $state['dH'];
        $this->playerState = $state['pS'];
        $this->dealerState = $state['dS'];
        $this->currentBet = $state['cB'];
        $this->isRoundFinished = $state['iRF'];
    }

    protected function generateDealerHand()
    {
        $dealerScore = rand(18, 21);
        return $this->generateTwoCardHandWithScore($dealerScore);
    }


    protected function generatePlayerHand($dealerHand)
    {
        $dealerScore = $this->calculateScore($dealerHand);
        $playerScore = rand(12, $dealerScore - 1);
        return $this->generateTwoCardHandWithScore($playerScore);
    }

    protected function generateTwoCardHandWithScore($targetScore)
    {
        $hand = [];
        $score = 0;

        while (count($hand) < 2) {
            $card = $this->generateRandomCard();

            if ($score + $this->getCardValue($card, $score) <= $targetScore || count($hand) == 1) {
                $hand[] = $card;
                $score = $this->calculateScore($hand);
            }
        }

        return $hand;
    }

    protected function getCardValue($card, $currentScore)
    {
        if (is_numeric($card['value'])) {
            return $card['value'];
        } elseif (in_array($card['value'], ['J', 'Q', 'K'])) {
            return 10;
        } elseif ($card['value'] === 'A') {
            return ($currentScore + 11 > 21) ? 1 : 11;
        }
        return 0;
    }

    protected function generateHandWithScore($targetScore)
    {
        $hand = [];
        $score = 0;

        while ($score < $targetScore) {
            $card = $this->generateRandomCard();
            $hand[] = $card;
            $score = $this->calculateScore($hand);
        }

        return $hand;
    }

    protected function generateRandomCard()
    {
        $values = [2, 3, 4, 5, 6, 7, 8, 9, 10, 'J', 'Q', 'K', 'A'];
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];

        $value = $values[array_rand($values)];
        $suit = $suits[array_rand($suits)];

        return [
            'value' => $value,
            'suit' => $suit
        ];
    }


    public function calculateScore($hand)
    {
        $score = 0;
        $aces = 0;

        foreach ($hand as $card) {
            if (is_numeric($card['value'])) {
                $score += $card['value'];
            } elseif (in_array($card['value'], ['J', 'Q', 'K'])) {
                $score += 10;
            } elseif ($card['value'] === 'A') {
                $aces += 1;
                $score += 11;
            }
        }

        while ($score > 21 && $aces > 0) {
            $score -= 10;
            $aces -= 1;
        }

        return $score;
    }

    public function getPlayerState()
    {
        return [
            'hand' => $this->playerHand,
            'state' => $this->playerState,
            'score' => $this->calculateScore($this->playerHand),
            'money' => $this->playerMoney,
            'currentBet' => $this->currentBet,
        ];
    }

    public function getDealerState()
    {
        if ($this->playerState === 'playing') {
            return [
                'hand' => [
                    array_slice($this->dealerHand, 0, count($this->dealerHand) - 1),
                ],
                'state' => $this->dealerState,
                'score' => count($this->dealerHand) ? $this->calculateScore([$this->dealerHand[0]]) : 0,
            ];
        }
        return [
            'hand' => $this->dealerHand,
            'state' => $this->dealerState,
            'score' => $this->calculateScore($this->dealerHand),
        ];
    }

    public function playerHit()
    {
        if ($this->playerState !== 'playing') {
            return;
        }

        $this->playerHand[] = $this->generateRandomCard();
        $playerScore = $this->calculateScore($this->playerHand);

        if ($playerScore > 21) {
            $this->playerState = 'busted';
        }
    }

    public function determineWinner()
    {
        $playerScore = $this->calculateScore($this->playerHand);
        $dealerScore = $this->calculateScore($this->dealerHand);

        if ($this->playerState === 'busted') {
            $this->resolveBet('dealer');
            return 'dealer';
        }

        if ($dealerScore === 21 && $playerScore === 21) {
            $this->resolveBet('tie');
            return 'tie';
        }

        if ($dealerScore > $playerScore || $dealerScore === 21) {
            $this->resolveBet('dealer');
            return 'dealer';
        }

        while ($dealerScore < $playerScore && $dealerScore < 21) {
            $need_score = $playerScore === 21 ? 21 - $dealerScore : $playerScore - $dealerScore + 1;
            if ($need_score > 11) {
                $need_score = 11;
            }
            $this->dealerHand[] = $this->getCardByValue($need_score);
            $dealerScore = $this->calculateScore($this->dealerHand);
        }

        if ($dealerScore > 21) {
            $this->resolveBet('player');
            return 'player';
        }

        if ($playerScore > $dealerScore) {
            $this->resolveBet('player');
            return 'player';
        } else if ($dealerScore > $playerScore) {
            $this->resolveBet('dealer');
            return 'dealer';
        } else {
            $this->resolveBet('tie');
            return 'tie';
        }
    }

    protected function getCardByValue(int $value)
    {
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $randomSuit = $suits[array_rand($suits)];

        $cardValue = $value;
        if ($value === 11) {
            $cardValue = 'J'; // Jack
        } elseif ($value === 12) {
            $cardValue = 'Q'; // Queen
        } elseif ($value === 13) {
            $cardValue = 'K'; // King
        } elseif ($value === 1) {
            $cardValue = 'A'; // Ace
        }

        return [
            'value' => $cardValue,
            'suit' => $randomSuit
        ];
    }

    public function dealerTurn()
    {
        while ($this->calculateScore($this->dealerHand) < 17) {
            $this->dealerHand[] = $this->generateRandomCard();
        }

        $dealerScore = $this->calculateScore($this->dealerHand);

        if ($dealerScore > 21) {
            $this->dealerState = 'busted';
        } else {
            $this->dealerState = 'standing';
        }
    }

    public function playerStand()
    {
        $this->playerState = 'standing';
    }

    public function playerSurrender()
    {
        $this->resolveBet('dealer');
        $this->currentBet = 0;
        $this->playerState = 'surrendered';
    }

    public function resolveBet($winner)
    {
        if ($winner === 'player') {
            $this->playerMoney += $this->currentBet * 2;
        } elseif ($winner === 'tie') {
            $this->playerMoney += $this->currentBet;
        }

        $this->isRoundFinished = true;
        $this->currentBet = 0;
    }
}
