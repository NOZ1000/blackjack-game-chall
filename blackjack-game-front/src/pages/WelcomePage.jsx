import React from 'react';
import { Link } from 'react-router-dom';

function WelcomePage() {
  return (
    <div
      className="min-h-screen flex flex-col items-center justify-center text-white"
      style={{
        backgroundImage: "url('/background.webp')",
        backgroundSize: 'cover',
        backgroundPosition: 'center',
      }}
    >
      <h1 className="text-4xl font-bold mb-6 bg-green-900 bg-opacity-60 px-4 py-2 rounded-xl"
    
      style={{
        background: 'rgba(0, 0, 0, 0.3)', // Semi-transparent white
        backdropFilter: 'blur(10px)', // Blur effect
        WebkitBackdropFilter: 'blur(10px)', // Blur effect for Safari
        border: '1px solid rgba(255, 255, 255, 0.3)', // Border for glass effect
        boxShadow: '0 4px 30px rgba(0, 0, 0, 0.1)', // Subtle shadow for depth
      }}

      >
        Welcome to Blackjack
      </h1>
      <Link
        to="/game"
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
      </Link>
    </div>
  );
}

export default WelcomePage;
