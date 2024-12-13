import React from 'react';

const CARD_ASSETS_BASE = '/assets/cards';

function CardDisplay({ title, cards }) {
  const getCardImage = (card) => {
    if (!card || !card.value || !card.suit) {
      return `${CARD_ASSETS_BASE}/back.svg`; // Default to back image
    }
    return `${CARD_ASSETS_BASE}/${card.value}_of_${card.suit}.svg`.toLowerCase();
  };

  return (
    <div className="mb-6 max-w-xl mx-auto">
      <h2 className="text-xl text-center font-bold mb-4 border">{title}</h2>
      <div className="flex space-x-4 mx-auto justify-center">
        {cards.map((card, index) => (
          <img
            key={index}
            src={getCardImage(card)}
            alt={getCardImage(null)}
            className="w-24 h-32"
          />
        ))}
      </div>
    </div>
  );
}

export default CardDisplay;
