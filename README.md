# CTF challenge

Whitebox web + crypto challenge.

---

# Description

I'm working on my online casino, and, well... you know... I don't want anyone to win money in my casino. But if you can crack my casino and earn over $777,777, I'll give you the flag. Good luck!
http://IP:3000/

## Attachments
Sources for backend (folder `./blackjack-game`)

--- 

# Deploy notes

To deploy task, run `docker-compose up --build`

And after compose end building `docker exec -it blackjack-game-backend /bin/bash`, run

- `cp .env.example.docker .env`
- `composer i`
- `php artisan key:gen`
- `php artisan migrate:fresh`

