# Rediriger un domaine externe vers OVH

Guide pour faire pointer un domaine (ex: `domains.pouark.com`) vers un site hébergé sur OVH (ex: `mondary.design`).

---

## Contexte

- **Domaine externe** : `pouark.com` (chez Spaceship)
- **Hébergement** : OVH (où est hébergé `mondary.design`)
- **Objectif** : `domains.pouark.com` affiche le site sans iframe

---

## Étape 1 : Trouver l'IP de l'hébergement OVH

Dans un terminal :
```bash
nslookup mondary.design
```

Résultat : `51.91.236.193` (ton IP peut être différente)

---

## Étape 2 : Configurer OVH (Multisite)

1. Connexion à **OVH** → **Web Cloud** → **Hébergements**
2. Onglet **Multisite**
3. Cliquer sur **"Ajouter un domaine ou sous-domaine"**
4. Choisir **"Ajouter un domaine externe"**
5. Entrer : `domains.pouark.com`
6. **Dossier racine** : `www/pk/pkdomains` (le chemin vers ton app)

   > **Important** : Le chemin doit inclure `www/` car c'est la vraie racine FTP

7. Cocher **SSL** si disponible
8. Valider

OVH affiche les enregistrements DNS à configurer (noter les infos).

---

## Étape 3 : Configurer Spaceship (DNS)

1. Connexion à **Spaceship** → **Domains** → **pouark.com** → **DNS**
2. Section **Custom Records** → **Add record**

Ajouter ces enregistrements :

| Type | Host | Value |
|------|------|-------|
| TXT | `ovhcontrol` | `[valeur donnée par OVH]` |
| A | `domains` | `51.91.236.193` |
| A | `www.domains` | `51.91.236.193` |

3. Sauvegarder

---

## Étape 4 : Attendre la propagation

- **DNS** : 5-30 minutes (parfois jusqu'à 24h)
- **SSL** : 5-15 minutes après que le DNS soit propagé

Vérifier la propagation : https://dnschecker.org

---

## Étape 5 : Activer le SSL

Une fois le diagnostic Multisite au vert :

1. **Multisite** → **"..."** à côté de `domains.pouark.com`
2. **Modifier** → Cocher **SSL**
3. Sauvegarder
4. Attendre 5-10 minutes

---

## Vérification finale

- [ ] `https://domains.pouark.com` affiche le site
- [ ] Pas d'erreur de certificat SSL
- [ ] Login fonctionne (cookies OK)

---

## Dépannage

### "Site non installé"
→ Attendre 15-30 minutes, OVH configure le multisite

### Erreur SSL / Certificat invalide
→ Regénérer le SSL depuis Multisite ou Certificats SSL

### "Index of /"
→ Mauvais dossier racine. Vérifier que c'est bien `www/pk/pkdomains` (avec `www/`)

### Diagnostic Multisite rouge/orange
→ DNS pas encore propagé, attendre ou vérifier les enregistrements sur Spaceship

---

## Résumé des enregistrements DNS

Pour `pouark.com` chez Spaceship :

```
ovhcontrol.pouark.com      TXT    [valeur OVH]
domains.pouark.com         A      51.91.236.193
www.domains.pouark.com     A      51.91.236.193
```
