# Writeup for Blackjack game

## Steps to solve

- Find dangerous functions
- Examine how encryption works
- You have `iv` and `encrypted_session`
- Examine how session is stored
- Notice that last byte of first encrypted session block, is first digit of you current money
- Imagine how to switch that byte

## Exploit POC

```python
import requests
from base64 import b64encode, b64decode

BASE_URL = 'http://localhost:8000/api'

# Session with 100 000$ (IMPORTANT)
enc = {
  "encryptedSession": "xzkhx4m0dRUDtaIaDC++r/QrbdJBz20nVZAigiXzUeA1OyocViLlvQRXu8SxVv9sXhGUEFor8/2knJ10/PexaflQ/EeJ9tIGITjXBXWBPGnnYyB16nn2+vwqMCBmmfmU0fv2w/TfyB2MkPIEThq1LDyCinEoovq9x7kCjTS2++w=", 
  "iv": "N7V41R1jtpxauVGHHuEk0A=="
}

for i in range(1, 256):
    iv = bytearray(b64decode(enc["iv"]))
    iv[15] = i
    to_restore = {
        "encryptedSession": enc["encryptedSession"],
        "iv": b64encode(iv)
    }

    res = requests.post(BASE_URL+'/game/restore', json=to_restore)

    if 'newUuid' in res.text:
        print(i, end=": \n")
        uuid = res.json()["newUuid"]
        print(uuid)
        res = requests.get(BASE_URL + f'/game/{uuid}/status')
        if 'money' in res.text: 
            print(res.text)
```
