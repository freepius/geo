# SAFER Notifications - Surveillance automatique

Ce système permet de surveiller automatiquement les notifications SAFER pour les communes de Divajeu et Chabrillan.

## 🔔 Installation de la surveillance automatique

### Option 1: Notifications desktop (GNOME)

Pour recevoir des notifications directement sur votre bureau Debian/GNOME :

```bash
./bin/install-cron.sh
```

Cela installera une tâche cron qui vérifie les notifications **chaque jour à 9h00**.

### Option 2: Notifications par email

Pour recevoir des emails (nécessite la configuration de `mailutils`) :

1. Installer mailutils :
```bash
sudo apt install mailutils
```

2. Éditer le fichier `bin/check-safer-notifications-email.sh` et remplacer `votre.email@example.com` par votre email

3. Ajouter à votre crontab :
```bash
crontab -e
```

Puis ajouter cette ligne :
```
0 9 * * * /chemin/vers/geo/bin/check-safer-notifications-email.sh votre.email@example.com
```

## 🧪 Test manuel

### Test de notification desktop
```bash
./bin/check-safer-notifications.sh
```

### Test de notification email
```bash
./bin/check-safer-notifications-email.sh votre.email@example.com
```

### Test avec la commande PHP directement
```bash
php bin/console safer:check-notifications Divajeu Chabrillan
```

## 📝 Logs

Les logs sont enregistrés dans :
```
var/log/safer-notifications.log
```

Pour voir les dernières notifications :
```bash
tail -f var/log/safer-notifications.log
```

## ⚙️ Configuration

### Changer les communes surveillées

Éditez les fichiers :
- `bin/check-safer-notifications.sh`
- `bin/check-safer-notifications-email.sh`

Modifiez la ligne :
```bash
COMMUNES=("Divajeu" "Chabrillan")
```

### Changer l'heure de vérification

Éditez votre crontab :
```bash
crontab -e
```

Format : `minute heure jour mois jour_semaine`

Exemples :
- `0 9 * * *` = tous les jours à 9h00
- `0 9,18 * * *` = tous les jours à 9h00 et 18h00
- `0 9 * * 1-5` = du lundi au vendredi à 9h00

## 🗑️ Désinstallation

Pour supprimer la tâche cron :
```bash
crontab -e
# Supprimer la ligne contenant "check-safer-notifications.sh"
```

## 📦 Dépendances

- PHP 8.2+
- Symfony HttpClient (déjà installé)
- `notify-send` (pour notifications desktop, généralement préinstallé sur GNOME)
- `jq` (optionnel, pour un meilleur parsing JSON) : `sudo apt install jq`
- `mailutils` (pour notifications email) : `sudo apt install mailutils`
