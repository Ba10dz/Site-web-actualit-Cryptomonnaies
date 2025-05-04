#!/usr/bin/env python3
# crypto_to_mariadb.py

import re
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

# Fonction de connexion à MariaDB
def connect_db():
    try:
        connection = mysql.connector.connect(
            host='51.83.68.96',
            database='crypto_db',
            user='akkari',         # Remplace avec ton utilisateur MariaDB
            password='20051002'    # Remplace avec ton mot de passe MariaDB
        )
        if connection.is_connected():
            return connection
    except Error as e:
        print(f"Erreur de connexion à MariaDB : {e}")
    return None

# Création de la table principale `cryptos` si elle n'existe pas
def create_cryptos_table(cursor):
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS cryptos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            symbol VARCHAR(20) UNIQUE
        );
    """)

# Fonction pour rendre les noms de tables sûrs (remplace tout ce qui n'est pas alphanumérique ou underscore)
def sanitize_symbol(symbol):
    return re.sub(r'[^0-9A-Za-z_]', '_', symbol.lower())

# Création d'une table secondaire pour chaque crypto
def create_crypto_table(cursor, symbol):
    table_name = sanitize_symbol(symbol) + '_prices'
    cursor.execute(f"""
        CREATE TABLE IF NOT EXISTS `{table_name}` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            price DECIMAL(18, 8),
            change_1h DECIMAL(6, 2),
            change_24h DECIMAL(6, 2),
            change_7d DECIMAL(6, 2),
            date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    """)

def main():
    response = requests.get(url, params=params)
    if response.status_code != 200:
        print(f"Erreur API : {response.status_code}")
        return

    data = response.json()
    connection = connect_db()
    if connection is None:
        print("Impossible de se connecter à la base de données.")
        return

    cursor = connection.cursor()
    create_cryptos_table(cursor)
    connection.commit()

    print("Mise à jour des cryptos et insertion des prix dans les tables dédiées...\n")

    for coin in data:
        name      = coin.get('name')
        symbol    = coin.get('symbol')
        price     = coin.get('current_price')
        change_1h = coin.get('price_change_percentage_1h_in_currency')
        change_24h= coin.get('price_change_percentage_24h_in_currency')
        change_7d = coin.get('price_change_percentage_7d_in_currency')

        # 1️⃣ Ajouter ou mettre à jour la crypto dans la table principale `cryptos`
        try:
            cursor.execute("""
                INSERT INTO cryptos (name, symbol) VALUES (%s, %s)
                ON DUPLICATE KEY UPDATE name = VALUES(name);
            """, (name, symbol))
            connection.commit()
        except Error as e:
            print(f"Erreur d'insertion dans cryptos ({symbol}) : {e}")

        # 2️⃣ Créer la table secondaire pour cette crypto si nécessaire
        try:
            create_crypto_table(cursor, symbol)
            connection.commit()
        except Error as e:
            print(f"Erreur création table pour {symbol} : {e}")

        # 3️⃣ Insérer le prix et les variations dans la table dédiée
        table_name = sanitize_symbol(symbol) + '_prices'
        try:
            cursor.execute(f"""
                INSERT INTO `{table_name}` (price, change_1h, change_24h, change_7d)
                VALUES (%s, %s, %s, %s);
            """, (price, change_1h, change_24h, change_7d))
            connection.commit()
        except Error as e:
            print(f"Erreur d'insertion dans {table_name} : {e}")

    cursor.close()
    connection.close()
    print("Toutes les données ont été insérées avec succès !")

if __name__ == '__main__':
    main()
