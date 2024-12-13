
import React, { useState } from 'react';
import axios from 'axios';
import CardDisplay from '../components/CardDisplay';

const API_BASE_URL = 'http://localhost:8000/api';
const CARD_ASSETS_BASE = '/assets/cards';

function GamePage() {
  const [uuid, setUuid] = useState(null);
  const [betAmount, setBetAmount] = useState('');
  const [playerHand, setPlayerHand] = useState([]);
  const [dealerHand, setDealerHand] = useState([]);
  const [playerScore, setPlayerScore] = useState(0); // Player score
  const [dealerScore, setDealerScore] = useState(0); // Dealer score
  const [playerBalance, setPlayerBalance] = useState(100000); // Default balance
  const [message, setMessage] = useState('');
  const [gameResult, setGameResult] = useState(null); // Tracks the game result (player wins, dealer wins, or tie)

  const startGame = async () => {
    try {
      const response = await axios.post(`${API_BASE_URL}/game`);
      setUuid(response.data.uuid);
      resetGameState();
      setMessage('Game started successfully!');
    } catch (error) {
      setMessage('Error starting game.');
    }
  };

  const placeBet = async () => {
    try {
      const response = await axios.post(`${API_BASE_URL}/game/${uuid}/bet`, {
        amount: parseInt(betAmount),
      });
      fetchGameState();
      setMessage('Bet placed successfully!');
    } catch (error) {
      if (error.response && error.response.data && error.response.data.error) {
        setMessage(error.response.data.error); // Use server-provided error message
        return;
      }
      setMessage('Error placing bet.');
    }
  };

  const performAction = async (action) => {
    try {
      const response = await axios.post(`${API_BASE_URL}/game/${uuid}/${action}`);
      fetchGameState();

      if (response.data.message) {
        setMessage(response.data.message);
        return;
      }

      if (response.data.result) {
        setGameResult(response.data.result); // Update result when game ends
        setMessage(
          response.data.result === 'player'
            ? 'You Win!'
            : response.data.result === 'dealer'
            ? 'Dealer Wins!'
            : "It's a Tie!"
        );
      }
    } catch (error) {
      if (error.response && error.response.data && error.response.data.error) {
        setMessage(error.response.data.error); // Use server-provided error message
        return;
      }
      setMessage(`Error performing action: ${action}`);
    }
  };

  const exportSession = async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/game/${uuid}/export`);
      const { encryptedSession, iv } = response.data;

      // Prepare the export file content
      const exportContent = JSON.stringify(
        { encryptedSession, iv },
        null,
        2 // Pretty-print JSON
      );

      // Create a Blob and download it as a file
      const blob = new Blob([exportContent], { type: 'application/json' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `game_session_${uuid}.json`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      setMessage('Game session exported successfully!');
    } catch (error) {
      setMessage('Error exporting game session.');
    }
  };

  const fetchGameState = async () => {
    try {
      const response = await axios.get(`${API_BASE_URL}/game/${uuid}/status`);
      const { playerState, dealerState } = response.data;

      // Ensure hands are always arrays
      setPlayerHand(Array.isArray(playerState.hand) ? playerState.hand : []);
      setDealerHand(Array.isArray(dealerState.hand) ? dealerState.hand : []);
      setPlayerScore(playerState.score || 0); // Set player score
      setDealerScore(dealerState.score || 0); // Set dealer score
      setPlayerBalance(playerState.money);
    } catch (error) {
      if (error.response && error.response.data && error.response.data.error) {
        setMessage(error.response.data.error); // Use server-provided error message
        return;
      }
      setMessage('Error fetching game state.');
    }
  };

  const resetGameState = () => {
    setBetAmount('');
    setPlayerHand([]);
    setDealerHand([]);
    setPlayerScore(0);
    setDealerScore(0);
    setGameResult(null);
  };

  const closeModal = () => {
    setGameResult(null); // Close popup
  };

  return (
    <div
      className="min-h-screen flex items-center justify-center"
      style={{
        backgroundImage: "url('/background.webp')",
        backgroundSize: 'cover',
        backgroundPosition: 'center',
      }}
    >
      <div
        className="p-8 rounded-lg shadow-lg text-white w-full max-w-4xl"
        style={{
          background: 'rgba(0, 0, 0, 0.3)', // Semi-transparent white
          backdropFilter: 'blur(10px)', // Blur effect
          WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
          border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
          boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
        }}
      >
        <h1 className="text-3xl font-bold text-center mb-6">Blackjack Game</h1>

        {/* Success/Error Messages */}
        {message && (
          <div
            className="bg-gray-600 text-white py-2 px-4 rounded mb-4 mx-auto max-w-xl text-center"
            style={{
              background: 'rgba(0, 0, 0, 0.3)', // Semi-transparent white
              backdropFilter: 'blur(10px)', // Blur effect
              WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
              border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
              boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
            }}
            onClick={() => setMessage('')} // Clear message on click
          >
            {message}
          </div>
        )}

        {/* Player Balance */}
        <div className="mb-4">
          <h2 className="text-xl font-bold text-center">Your Balance: ${playerBalance}</h2>
        </div>

        {/* Start Game */}
        {!uuid ? (
          <div className="flex justify-center">
            <button
              onClick={startGame}
              className="bg-green-600 font-bold text-lg hover:bg-green-700 text-white py-3 px-6 rounded"
              style={{
                background: 'rgba(0, 150, 0, 0.6)', // Semi-transparent white
                backdropFilter: 'blur(10px)', // Blur effect
                WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
                border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
                boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
              }}
            >
              Start Game
            </button>
          </div>
        ) : (
          <div>
            {/* Betting Section */}
            <div className="mb-6 mx-auto max-w-xs">
              <label className="block text-lg mb-2">Place Your Bet:</label>
              <div className="flex">
                <input
                  type="number"
                  value={betAmount}
                  onChange={(e) => setBetAmount(e.target.value)}
                  className="w-full p-2 rounded-l bg-green-700 text-white"
                  min="10000" // Minimum value
                  step="5000" // Step value
                  placeholder="Enter bet amount"

                  style={{
                    background: 'rgba(0, 0, 0, 0.3)', // Semi-transparent white
                    backdropFilter: 'blur(10px)', // Blur effect
                    WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
                    border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
                    boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
                  }}
                />
                <button
                  onClick={placeBet}
                  className="bg-green-600 font-bold hover:bg-green-700 text-white py-2 px-4 rounded-r"
                  style={{
                    background: 'rgba(0, 0, 0, 0.3)', // Semi-transparent white
                    backdropFilter: 'blur(10px)', // Blur effect
                    WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
                    border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
                    boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
                  }}
                >
                  Place Bet
                </button>
              </div>
            </div>

            {/* Game Actions */}
            <div className="flex justify-center space-x-4 mb-6">
              <button
                onClick={() => performAction('hit')}
                className="bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded"
                style={{
                  background: 'rgba(0, 150, 0, 0.6)', // Semi-transparent white
                  backdropFilter: 'blur(10px)', // Blur effect
                  WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
                  border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
                  boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
                }}

              >
                Hit
              </button>
              <button
                onClick={() => performAction('stand')}
                className="bg-orange-600 hover:bg-orange-700 text-white py-2 px-4 rounded"
                style={{
                  background: 'rgba(255, 128, 0, 0.6)', // Semi-transparent white
                  backdropFilter: 'blur(10px)', // Blur effect
                  WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
                  border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
                  boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
                }}

              >
                Stand
              </button>
              <button
                onClick={() => performAction('surrender')}
                className="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded"
                style={{
                  background: 'rgba(150, 0, 0, 0.6)', // Semi-transparent white
                  backdropFilter: 'blur(10px)', // Blur effect
                  WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
                  border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
                  boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
                }}
              >
                Surrender
              </button>
              <button
                onClick={() => exportSession()}
                className="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded"
                style={{
                  background: 'rgba(150, 155, 155, 0.6)', // Semi-transparent white
                  backdropFilter: 'blur(10px)', // Blur effect
                  WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
                  border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
                  boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
                }}
              >
                Export
              </button>

            </div>

            <div className="grid grid-cols-2 gap-4 mb-6">
              {/* Player's Hand */}
              <div className="text-center">
                <h2 className="text-xl font-bold mb-2">Player's Hand</h2>
                <CardDisplay cards={playerHand} />
                <p className="text-lg font-bold mt-2">Score: {playerScore}</p>
              </div>
              {/* Dealer's Hand */}
              <div className="text-center">
                <h2 className="text-xl font-bold mb-2">Dealer's Hand</h2>
                <CardDisplay cards={dealerHand} />
                <p className="text-lg font-bold mt-2">Score: {dealerScore}</p>
              </div>
            </div>

            {/* Game Result Modal */}
            {gameResult && (
              <div className="fixed inset-0 bg-black bg-opacity-20 flex items-center justify-center">
                <div className="bg-green-700 text-white p-6 rounded"
                    style={{
                      background: 'rgba(0, 100, 0, 0.5)', // Semi-transparent white
                      backdropFilter: 'blur(10px)', // Blur effect
                      WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
                      border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
                      boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
                    }}
              >
                  <h2 className="text-2xl font-bold mb-4">Game Over</h2>
                  <p className="text-lg font-bold">
                    {gameResult === 'player'
                      ? 'You Win!'
                      : gameResult === 'dealer'
                      ? 'Dealer Wins!'
                      : "It's a Tie!"}
                  </p>
                  <button
                    onClick={closeModal}
                    className="mt-4 bg-red-600 hover:bg-red-700 py-2 px-4 rounded"
                    style={{
                      background: 'rgba(0, 150, 0, 0.2)', // Semi-transparent white
                      backdropFilter: 'blur(10px)', // Blur effect
                      WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
                      border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
                      boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
                    }}

                  >
                    Close
                  </button>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

export default GamePage;
