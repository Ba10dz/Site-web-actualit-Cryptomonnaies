#!/usr/bin/env python3
# mise_a_jour_site.py

import requests
import mysql.connector
from mysql.connector import Error

# URL de l'API CoinGecko
url = 'https://api.coingecko.com/api/v3/coins/markets'

# Paramètres pour récupérer les cryptos en USD, avec variations 1h, 24h et 7j
params = {
    'vs_currency': 'usd',
    'order': 'market_cap_desc',
    'per_page': 40,
    'page': 1,
    'sparkline': 'false',
    'price_change_percentage': '1h,24h,7d'
}

def connect_db():
    try:
        connection = mysql.connector.connect(
            host='51.83.68.96',
            database='crypto_db',
            user='akkari',      
            password='20051002' 
        )
        if connection.is_connected():
            return connection
    except Error as e:
        print(f"Erreur de connexion à MariaDB : {e}")
    return None

def create_crypto_table(cursor):
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS crypto_prices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50),
            symbol VARCHAR(10) UNIQUE,
            image_url VARCHAR(255),
            graph_url VARCHAR(255),
            CryptoPage VARCHAR(255),
            price DECIMAL(18, 8),
            change_1h DECIMAL(5, 2),
            change_24h DECIMAL(5, 2),
            change_7d DECIMAL(5, 2),
            date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
    """)

def main():
    # 1️⃣ Récupération du top-40 depuis CoinGecko
    response = requests.get(url, params=params)
    if response.status_code != 200:
        print(f"Erreur API : {response.status_code}")
        return
    data = response.json()
    # Liste des symboles actuels
    symbols = [coin['symbol'].upper() for coin in data]

    # 2️⃣ Connexion et nettoyage de la table centralisée
    connection = connect_db()
    if not connection:
        print("Impossible de se connecter à la base de données.")
        return
    cursor = connection.cursor()

    # Création de la table si besoin
    create_crypto_table(cursor)

    # Supprimer les anciens symboles hors top-40
    if symbols:
        placeholders = ",".join(["%s"] * len(symbols))
        delete_sql = f"DELETE FROM crypto_prices WHERE symbol NOT IN ({placeholders})"
        cursor.execute(delete_sql, symbols)
        connection.commit()

    print("Mise à jour des cryptos (top-40 seulement)...\n")

    # 3️⃣ Upsert des 40 cryptos
    for coin in data:
        name    = coin['name']
        symbol  = coin['symbol'].upper()
        price   = coin['current_price']
        if price >= 1:
            price = round(price, 2)
        else:
            price = round(price, 8)
        change_1h  = coin.get('price_change_percentage_1h_in_currency', None)
        change_24h = coin.get('price_change_percentage_24h_in_currency', None)
        change_7d  = coin.get('price_change_percentage_7d_in_currency', None)
        image_url  = coin.get('image', None)

        # URLs dépendant du symbole
        graph_url   = f"http://51.83.68.96/home/TS2/BOURSE/siteamelioration/graphique/{symbol.lower()}.png"
        crypto_page = f"http://51.83.68.96/home/TS2/BOURSE/siteamelioration/{symbol.lower()}.php"

        try:
            cursor.execute("""
                INSERT INTO crypto_prices
                    (name, symbol, image_url, graph_url, CryptoPage,
                     price, change_1h, change_24h, change_7d)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    image_url  = VALUES(image_url),
                    graph_url  = VALUES(graph_url),
                    CryptoPage = VALUES(CryptoPage),
                    price      = VALUES(price),
                    change_1h  = VALUES(change_1h),
                    change_24h = VALUES(change_24h),
                    change_7d  = VALUES(change_7d),
                    date       = CURRENT_TIMESTAMP
            """, (
                name, symbol, image_url, graph_url, crypto_page,
                price, change_1h, change_24h, change_7d
            ))
            connection.commit()
        except Error as e:
            print(f"Erreur pour {symbol} : {e}")

    cursor.close()
    connection.close()
    print("Table crypto_prices ramenée au top-40 et mise à jour !")

if __name__ == "__main__":
    main()
