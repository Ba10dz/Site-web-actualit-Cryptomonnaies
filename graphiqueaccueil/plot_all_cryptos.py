#!/usr/bin/env python3
# plot_all_cryptos.py

import mysql.connector
import matplotlib.pyplot as plt
import datetime

# Connexion à la base de données MariaDB
conn = mysql.connector.connect(
    host="51.83.68.96",
    user="akkari",            # Remplace par ton utilisateur MariaDB
    password="20051002",      # Remplace par ton mot de passe MariaDB
    database="crypto_db"      # Remplace par le nom de ta base de données
)
cursor = conn.cursor()

# 1️⃣ Récupérer la liste de toutes les tables de prix (exclut la table 'crypto_prices' centrale)
cursor.execute("SHOW TABLES LIKE '%\\_prices'")
tables = [t[0] for t in cursor.fetchall() if t[0] != 'crypto_prices']

for table in tables:
    # extraire le symbole (ex. 'btc' depuis 'btc_prices')
    symbol = table.replace('_prices', '').upper()
    
    # 2️⃣ Récupérer l'historique date/price
    cursor.execute(f"SELECT date, price FROM {table} ORDER BY date ASC")
    data = cursor.fetchall()
    if not data:
        print(f"Aucune donnée dans {table}, on passe.")
        continue

    dates, prices = zip(*data)
    # convertir en datetime
    dates = [
        datetime.datetime.strptime(str(d), "%Y-%m-%d %H:%M:%S")
        for d in dates
    ]

    # 3️⃣ Générer le graphique sans axes ni cadre (courbe sur fond blanc)
    plt.figure(figsize=(12, 6), facecolor='white')
    plt.plot(dates, prices, color='black', linewidth=8)
    plt.axis('off')  # supprime axes, graduations et cadres

    # 4️⃣ Sauvegarder l'image
    filename = f"{symbol.lower()}.png"
    plt.savefig(filename)
    plt.close()  # libère la figure
    print(f"Enregistré : {filename}")

# 5️⃣ Nettoyage
cursor.close()
conn.close()
