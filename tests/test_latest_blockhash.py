import pytest
from solana.rpc.api import Client

client = Client("https://api.devnet.solana.com")

def test_latest_blockhash():
    response = client.get_latest_blockhash()
    assert response is not None

    # response lehet objektum vagy dict; próbáljuk meg több módon elérni a blockhash-t
    value = getattr(response, "value", None) or getattr(response, "result", None)

    if isinstance(value, dict):
        blockhash = value.get("blockhash") or value.get("value", {}).get("blockhash")
    else:
        blockhash = getattr(value, "blockhash", None)

    assert blockhash, "Nem találtam a blockhash mezőt a válaszban"