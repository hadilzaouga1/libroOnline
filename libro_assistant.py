import sys
import io
import os

# SOLUTION ROBUSTE POUR L'ENCODAGE
def fix_encoding():
    """Forcer l'encodage UTF-8 pour stdout et stderr"""
    try:
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
        sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')
    except Exception:
        sys.stdout = sys.__stdout__
        sys.stderr = sys.__stderr__
    
    os.environ['PYTHONIOENCODING'] = 'utf-8'
    os.environ['PYTHONUTF8'] = '1'

fix_encoding()

import mysql.connector
from mysql.connector import Error
from datetime import datetime
import json
import re
import logging
import sys
from typing import Dict, List, Optional, Tuple, Any

# Désactiver les logs
logging.basicConfig(level=logging.CRITICAL)
logger = logging.getLogger(__name__)
logger.setLevel(logging.CRITICAL)

class LibroAssistant:
    def __init__(self, db_config: Dict[str, str], user_email: str = None):
        self.db_config = db_config
        self.conn = None
        self.cursor = None
        self.context = {}
        self.conversation_history = []
        self.user_email = user_email
        
    def connect_db(self) -> bool:
        try:
            if self.conn and self.conn.is_connected():
                return True
            self.conn = mysql.connector.connect(**self.db_config)
            self.cursor = self.conn.cursor(dictionary=True)
            return True
        except Error:
            return False
    
    def close_db(self):
        try:
            if self.cursor:
                self.cursor.close()
            if self.conn and self.conn.is_connected():
                self.conn.close()
        except Error:
            pass
    
    def execute_query(self, query: str, params: Tuple = None) -> bool:
        try:
            if not self.connect_db():
                return False
            self.cursor.execute(query, params or ())
            self.conn.commit()
            return True
        except Error:
            if self.conn:
                self.conn.rollback()
            return False
    
    def save_conversation(self, user_message: str, assistant_response: str, user_email: str = None, session_id: str = None):
        try:
            user_email = user_email if user_email else 'anonymous'
            session_id = session_id if session_id else 'unknown'
            
            user_message = user_message.replace('"', '\\"').replace("'", "\\'")
            assistant_response = assistant_response.replace('"', '\\"').replace("'", "\\'")
            
            query = """
                INSERT INTO assistant_conversations 
                (user_email, user_message, assistant_response, session_id) 
                VALUES (%s, %s, %s, %s)
            """
            params = (user_email, user_message, assistant_response, session_id)
            
            return self.execute_query(query, params)
        except Exception:
            return False

    # NOUVELLES FONCTIONNALITÉS AJOUTÉES
    def get_user_stats(self, user_email: str) -> Dict[str, Any]:
        """Obtenir les statistiques de l'utilisateur"""
        try:
            if not self.connect_db():
                return {}
            
            # Livres achetés
            query_purchases = "SELECT COUNT(*) as count FROM user_library WHERE user_email = %s AND type = 'purchase'"
            self.cursor.execute(query_purchases, (user_email,))
            purchases = self.cursor.fetchone()['count']
            
            # Livres empruntés
            query_borrows = "SELECT COUNT(*) as count FROM user_library WHERE user_email = %s AND type = 'borrow'"
            self.cursor.execute(query_borrows, (user_email,))
            borrows = self.cursor.fetchone()['count']
            
            # Wishlist
            query_wishlist = "SELECT COUNT(*) as count FROM wishlist WHERE user_email = %s"
            self.cursor.execute(query_wishlist, (user_email,))
            wishlist = self.cursor.fetchone()['count']
            
            return {
                'purchases': purchases,
                'borrows': borrows,
                'wishlist': wishlist
            }
        except Error:
            return {}

    def get_book_suggestions(self, user_interests: List[str] = None) -> List[Dict]:
        """Suggérer des livres basés sur les intérêts"""
        try:
            if not self.connect_db():
                return []
            
            if user_interests:
                # Recherche basée sur les intérêts
                placeholders = ', '.join(['%s'] * len(user_interests))
                query = f"""
                    SELECT * FROM books 
                    WHERE category IN ({placeholders}) 
                    AND available = 1 
                    ORDER BY RAND() 
                    LIMIT 5
                """
                self.cursor.execute(query, user_interests)
            else:
                # Livres populaires par défaut
                query = """
                    SELECT b.*, COUNT(r.id) as review_count
                    FROM books b
                    LEFT JOIN reviews r ON b.id = r.book_id
                    WHERE b.available = 1
                    GROUP BY b.id
                    ORDER BY review_count DESC, b.title
                    LIMIT 5
                """
                self.cursor.execute(query)
            
            return self.cursor.fetchall()
        except Error:
            return []

    def get_reading_tips(self) -> List[str]:
        """Conseils de lecture"""
        tips = [
            "📖 Essayez de lire 20 minutes par jour pour développer une habitude de lecture régulière",
            "🎯 Fixez-vous un objectif de lecture réaliste (ex: 1 livre par mois)",
            "📚 Variez les genres pour découvrir de nouveaux auteurs et styles",
            "⏰ Trouvez le moment de la journée où vous êtes le plus concentré pour lire",
            "🔍 Notez les citations et passages qui vous inspirent",
            "💡 Rejoignez un club de lecture pour échanger avec d'autres lecteurs",
            "🌟 Lisez les avis avant de choisir un livre pour mieux le sélectionner"
        ]
        return tips

    def get_author_info(self, author_name: str) -> Dict[str, Any]:
        """Obtenir des informations sur un auteur"""
        try:
            if not self.connect_db():
                return {}
            
            query = """
                SELECT 
                    COUNT(*) as book_count,
                    GROUP_CONCAT(DISTINCT category) as categories,
                    MIN(price) as min_price,
                    MAX(price) as max_price
                FROM books 
                WHERE author LIKE %s
                GROUP BY author
            """
            self.cursor.execute(query, (f"%{author_name}%",))
            result = self.cursor.fetchone()
            
            if result:
                return {
                    'book_count': result['book_count'],
                    'categories': result['categories'].split(',') if result['categories'] else [],
                    'price_range': f"{result['min_price']} - {result['max_price']} TND"
                }
            return {}
        except Error:
            return {}

    # FONCTIONS EXISTANTES AMÉLIORÉES
    def get_books(self, filters: Dict[str, Any] = None, limit: int = 10) -> List[Dict]:
        try:
            if not self.connect_db():
                return []
                
            query = "SELECT * FROM books WHERE 1=1"
            params = []
            
            if filters:
                if filters.get('category'):
                    query += " AND category LIKE %s"
                    params.append(f"%{filters['category']}%")
                if filters.get('author'):
                    query += " AND author LIKE %s"
                    params.append(f"%{filters['author']}%")
                if filters.get('available') is not None:
                    query += " AND available = %s"
                    params.append(1 if filters['available'] else 0)
                if filters.get('max_price'):
                    query += " AND price <= %s"
                    params.append(filters['max_price'])
                if filters.get('language'):
                    query += " AND language = %s"
                    params.append(filters['language'])
            
            query += " ORDER BY title LIMIT %s"
            params.append(limit)
            
            self.cursor.execute(query, params)
            return self.cursor.fetchall()
        except Error:
            return []

    def search_books(self, keyword: str, limit: int = 10) -> List[Dict]:
        try:
            if not self.connect_db():
                return []
                
            query = """
                SELECT * FROM books 
                WHERE title LIKE %s 
                OR author LIKE %s 
                OR description LIKE %s
                OR category LIKE %s
                ORDER BY 
                    CASE 
                        WHEN title LIKE %s THEN 1
                        WHEN author LIKE %s THEN 2
                        ELSE 3
                    END,
                    title
                LIMIT %s
            """
            search_term = f"%{keyword}%"
            exact_term = f"{keyword}%"
            params = [search_term, search_term, search_term, search_term, exact_term, exact_term, limit]
            
            self.cursor.execute(query, params)
            return self.cursor.fetchall()
        except Error:
            return []
    
    def get_categories(self) -> List[str]:
        try:
            if not self.connect_db():
                return []
                
            query = "SELECT DISTINCT category FROM books WHERE category IS NOT NULL ORDER BY category"
            self.cursor.execute(query)
            result = self.cursor.fetchall()
            return [row['category'] for row in result] if result else []
        except Error:
            return []
    
    def get_recommendations(self, category: str = None, limit: int = 5) -> List[Dict]:
        try:
            if not self.connect_db():
                return []
                
            if category:
                query = "SELECT * FROM books WHERE category = %s AND available = 1 ORDER BY title LIMIT %s"
                params = (category, limit)
            else:
                query = "SELECT * FROM books WHERE available = 1 ORDER BY RAND() LIMIT %s"
                params = (limit,)
            
            self.cursor.execute(query, params)
            return self.cursor.fetchall()
        except Error:
            return []

    def format_book_info(self, book: Dict) -> str:
        try:
            price = float(book['price']) if book.get('price') else 0.0
        except (ValueError, TypeError):
            price = 0.0
            
        description = book.get('description', 'Aucune description disponible.')
        if len(description) > 200:
            description = description[:197] + "..."
            
        availability = "✅ Disponible" if book.get('available') else "❌ Indisponible"
        
        return f"""
📚 **{book.get('title', 'Titre inconnu')}**
✍️ **Auteur:** {book.get('author', 'Inconnu')}
📁 **Catégorie:** {book.get('category', 'Non catégorisé')}
💰 **Prix:** {price:.3f} TND
🌍 **Langue:** {book.get('language', 'Non spécifié')}
{availability}

📖 **Description:** {description}
"""
    
    def format_books_list(self, books: List[Dict]) -> str:
        if not books:
            return "🔍 Aucun livre trouvé correspondant à votre recherche."
        
        response = f"📚 J'ai trouvé {len(books)} livre(s):\n\n"
        for i, book in enumerate(books, 1):
            title = book.get('title', 'Titre inconnu')
            author = book.get('author', 'Auteur inconnu')
            try:
                price = float(book.get('price', 0))
            except (ValueError, TypeError):
                price = 0.0
                
            availability = "✅" if book.get('available') else "❌"
            response += f"{i}. {availability} {title} par {author} - {price:.3f} TND\n"
        
        response += "\n💡 Utilisez 'détails [numéro]' pour plus d'informations sur un livre."
        return response

    # DÉTECTION D'INTENTIONS AMÉLIORÉE
    def process_intent(self, user_message: str) -> Tuple[str, Any]:
        message = user_message.lower().strip()
        
        # Salutations étendues
        if any(word in message for word in ['bonjour', 'salut', 'hello', 'coucou', 'hey', 'salutations']):
            return ('greeting', None)
        
        # Recherche étendue
        if any(word in message for word in ['cherche', 'recherche', 'trouve', 'livre', 'bouquin', 'titre', 'oeuvre']):
            keyword = self.extract_search_keywords(user_message)
            return ('search', keyword)
        
        # Recommandations étendues
        if any(word in message for word in ['recommande', 'suggère', 'conseil', 'propose', 'idée', 'suggestion']):
            category = self.extract_category(user_message)
            return ('recommend', category)
        
        # Catégories
        if any(word in message for word in ['catégorie', 'genre', 'type', 'catégories', 'thème']):
            return ('categories', None)
        
        # Livres populaires
        if any(word in message for word in ['populaire', 'meilleur', 'top', 'best', 'mieux noté', 'tendance']):
            return ('popular', None)
        
        # Détails d'un livre
        detail_match = re.search(r'détails?\s+(\d+)', message)
        if detail_match:
            return ('details', int(detail_match.group(1)))
        
        # Prix et budget
        if any(word in message for word in ['prix', 'coût', 'combien', 'tarif', 'budget', 'cher', 'bon marché']):
            return ('price', None)
        
        # Disponibilité
        if any(word in message for word in ['disponible', 'stock', 'dispo', 'disponibilité', 'en stock']):
            return ('available', None)
        
        # Aide étendue
        if any(word in message for word in ['aide', 'help', 'comment', 'que faire', 'assistance', 'guide']):
            return ('help', None)
        
        # Statistiques utilisateur
        if any(word in message for word in ['statistique', 'stats', 'mes livres', 'ma bibliothèque', 'mon compte']):
            return ('stats', None)
        
        # Conseils de lecture
        if any(word in message for word in ['conseil', 'astuce', 'tip', 'comment lire', 'habitude lecture']):
            return ('tips', None)
        
        # Informations auteur
        author_match = re.search(r'(auteur|écrivain|écrivaine)\s+([^\?\.]+)', message)
        if author_match:
            return ('author', author_match.group(2).strip())
        
        # Suggestions personnalisées
        if any(word in message for word in ['suggestion', 'me conseille', 'pour moi', 'selon mes goûts']):
            return ('suggest', None)
        
        # Nouveautés
        if any(word in message for word in ['nouveau', 'nouveauté', 'récent', 'dernier']):
            return ('new', None)
        
        # Promotions
        if any(word in message for word in ['promo', 'réduction', 'solde', 'offre', 'rabais']):
            return ('promo', None)
        
        # Merci
        if any(word in message for word in ['merci', 'thanks', 'thank you', 'merci beaucoup']):
            return ('thanks', None)
        
        # Au revoir
        if any(word in message for word in ['au revoir', 'bye', 'à plus', 'quit', 'exit', 'à bientôt']):
            return ('goodbye', None)
        
        return ('unknown', None)

    def extract_search_keywords(self, message: str) -> Optional[str]:
        patterns = [
            r'(?:cherche|recherche|trouve)\s+(?:un\s+)?(?:livre\s+)?(?:sur\s+)?(?:le\s+)?(?:sujet\s+)?["\']?([^"\']+)["\']?',
            r'(?:je\s+)?(?:veux|voudrais|cherche)\s+(?:un\s+)?livre\s+(?:sur|à\s+propos\s+de|concernant)\s+["\']?([^"\']+)["\']?',
            r'(?:donne|montre)\s+moi\s+(?:des\s+)?livres?\s+(?:sur|à\s+propos\s+de)\s+["\']?([^"\']+)["\']?',
            r'(?:livre|bouquin)\s+(?:sur|à\s+propos\s+de)\s+["\']?([^"\']+)["\']?'
        ]
        
        for pattern in patterns:
            matches = re.findall(pattern, message, re.IGNORECASE)
            if matches:
                keyword = matches[0].strip()
                keyword = re.sub(r'\b(?:livre|sur|à\s+propos|concernant|le|la|les|un|une|des)\b', '', keyword, flags=re.IGNORECASE)
                keyword = re.sub(r'\s+', ' ', keyword).strip()
                return keyword if keyword else None
                
        return None
    
    def extract_category(self, message: str) -> Optional[str]:
        categories = self.get_categories()
        message_lower = message.lower()
        
        for category in categories:
            if category.lower() in message_lower:
                return category
                
        return None

    def get_book_details_by_index(self, books: List[Dict], index: int) -> Optional[Dict]:
        if 1 <= index <= len(books):
            return books[index - 1]
        return None

    # RÉPONSE PRINCIPALE ENRICHIE
    def respond(self, user_message: str, session_id: str = None) -> str:
        self.conversation_history.append(('user', user_message))
        
        intent, param = self.process_intent(user_message)
        
        try:
            if intent == 'greeting':
                response = "👋 Bonjour ! Je suis l'assistant virtuel de Libro Online. Je peux vous aider à :\n• 🔍 Rechercher des livres\n• 💡 Obtenir des recommandations\n• 📚 Explorer les catégories\n• ⭐ Découvrir les livres populaires\n• 📖 Voir les détails des livres\n• 📊 Consulter vos statistiques\n\nComment puis-je vous aider aujourd'hui ?"
            
            elif intent == 'search':
                if param:
                    books = self.search_books(param)
                    if books:
                        self.context['last_search_results'] = books
                        self.context['last_search_query'] = param
                        response = f"🔍 J'ai trouvé {len(books)} livre(s) pour '{param}':\n\n"
                        response += self.format_books_list(books)
                    else:
                        response = f"🔍 Aucun livre trouvé pour '{param}'. Essayez avec d'autres mots-clés ou consultez les catégories disponibles."
                else:
                    response = "🔍 Que souhaitez-vous rechercher ? Donnez-moi un titre, un auteur, un sujet ou une catégorie."
            
            elif intent == 'details':
                if isinstance(param, int):
                    books = self.context.get('last_search_results', [])
                    if books:
                        book = self.get_book_details_by_index(books, param)
                        if book:
                            response = self.format_book_info(book)
                        else:
                            response = f"❌ Aucun livre trouvé avec le numéro {param}. Veuillez choisir un numéro entre 1 et {len(books)}."
                    else:
                        response = "❌ Aucune recherche précédente trouvée. Veuillez d'abord effectuer une recherche."
                else:
                    response = "❌ Veuillez spécifier un numéro de livre (ex: 'détails 1')"
            
            elif intent == 'recommend':
                books = self.get_recommendations(category=param)
                if books:
                    if param:
                        response = f"💡 Voici mes recommandations en {param}:\n\n"
                    else:
                        response = "💡 Voici quelques livres que je vous recommande:\n\n"
                    response += self.format_books_list(books)
                    self.context['last_search_results'] = books
                else:
                    category_msg = f" dans la catégorie {param}" if param else ""
                    response = f"❌ Désolé, je n'ai pas trouvé de recommandations{category_msg} pour le moment."
            
            elif intent == 'categories':
                categories = self.get_categories()
                if categories:
                    response = "📚 Nos catégories disponibles:\n\n"
                    response += "\n".join([f"• {cat}" for cat in categories])
                    response += "\n\n💡 Dites-moi une catégorie pour voir les livres correspondants !"
                else:
                    response = "❌ Aucune catégorie disponible pour le moment."
            
            elif intent == 'popular':
                books = self.get_recommendations(limit=8)
                if books:
                    response = "🏆 Les livres les plus populaires en ce moment:\n\n"
                    response += self.format_books_list(books)
                    self.context['last_search_results'] = books
                else:
                    response = "❌ Aucun livre populaire trouvé pour le moment."
            
            elif intent == 'price':
                # Livres à différents prix
                budget_books = self.get_books(filters={'max_price': 50}, limit=5)
                if budget_books:
                    response = "💰 Voici quelques livres à petit budget (moins de 50 TND):\n\n"
                    response += self.format_books_list(budget_books)
                else:
                    response = "💰 Utilisez 'recherche [mot-clé]' puis filtrez par prix pour trouver des livres dans votre budget."
            
            elif intent == 'available':
                available_books = self.get_books(filters={'available': True}, limit=8)
                response = "✅ Livres disponibles en ce moment:\n\n"
                response += self.format_books_list(available_books)
                self.context['last_search_results'] = available_books
            
            elif intent == 'stats':
                if self.user_email and self.user_email != 'anonymous':
                    stats = self.get_user_stats(self.user_email)
                    response = f"📊 Vos statistiques de lecture {self.user_email}:\n\n"
                    response += f"• 📚 Livres achetés: {stats.get('purchases', 0)}\n"
                    response += f"• ⏰ Livres empruntés: {stats.get('borrows', 0)}\n"
                    response += f"• 💝 Wishlist: {stats.get('wishlist', 0)}\n"
                    response += f"• 📖 Total: {stats.get('purchases', 0) + stats.get('borrows', 0)} livres\n"
                else:
                    response = "📊 Connectez-vous pour voir vos statistiques de lecture personnelles !"
            
            elif intent == 'tips':
                tips = self.get_reading_tips()
                response = "💡 Conseils de lecture pour vous:\n\n"
                for i, tip in enumerate(tips, 1):
                    response += f"{i}. {tip}\n"
            
            elif intent == 'author':
                if param:
                    author_info = self.get_author_info(param)
                    books = self.search_books(param, limit=5)
                    if books:
                        response = f"✍️ Informations sur {param}:\n\n"
                        response += f"• 📚 Nombre de livres: {author_info.get('book_count', 0)}\n"
                        if author_info.get('categories'):
                            response += f"• 📁 Catégories: {', '.join(author_info['categories'][:3])}\n"
                        response += f"• 💰 Fourchette de prix: {author_info.get('price_range', 'N/A')}\n\n"
                        response += "📖 Quelques livres de cet auteur:\n"
                        for i, book in enumerate(books[:3], 1):
                            response += f"{i}. {book['title']} - {book['price']} TND\n"
                    else:
                        response = f"❌ Aucun livre trouvé pour l'auteur '{param}'."
                else:
                    response = "❌ Veuillez spécifier un nom d'auteur."
            
            elif intent == 'suggest':
                # Suggestions basées sur l'historique ou aléatoires
                books = self.get_book_suggestions()
                if books:
                    response = "🎯 Voici quelques suggestions spécialement pour vous:\n\n"
                    response += self.format_books_list(books)
                    self.context['last_search_results'] = books
                else:
                    response = "💡 Dites-moi quels genres vous aimez pour des suggestions plus personnalisées !"
            
            elif intent == 'new':
                # Livres récemment ajoutés (simulé)
                books = self.get_books(limit=6)
                if books:
                    response = "🆕 Découvrez nos dernières nouveautés:\n\n"
                    response += self.format_books_list(books)
                    self.context['last_search_results'] = books
                else:
                    response = "❌ Aucune nouveauté pour le moment."
            
            elif intent == 'promo':
                # Livres en promotion (simulé - livres à prix réduit)
                promo_books = self.get_books(filters={'max_price': 40}, limit=5)
                if promo_books:
                    response = "🎉 Promotions du moment - Livres à prix réduit:\n\n"
                    response += self.format_books_list(promo_books)
                    self.context['last_search_results'] = promo_books
                else:
                    response = "💡 Consultez régulièrement notre catalogue pour découvrir les promotions !"
            
            elif intent == 'help':
                response = """
🤖 **Assistant Libro Online - Guide Complet**

🎯 **CE QUE JE PEUX FAIRE :**

🔍 **Recherche :**
• "Cherche des livres de science-fiction"
• "Trouve des romans policiers"
• "Recherche Stephen King"

💡 **Recommandations :**
• "Recommande-moi des livres"
• "Suggestions de romans"
• "Livres populaires en fantasy"

📚 **Exploration :**
• "Catégories disponibles"
• "Livres en français"
• "Nouveautés"

📊 **Personnel :**
• "Mes statistiques" (connecté)
• "Conseils de lecture"
• "Suggestions pour moi"

💰 **Budget :**
• "Livres pas chers"
• "Promotions"
• "Budget 30 TND"

👨‍💼 **Auteurs :**
• "Auteur Victor Hugo"
• "Livres de cet écrivain"

💡 **Astuces :**
• Après une recherche, utilisez 'détails 1' pour voir les infos d'un livre
• Spécifiez votre budget pour des suggestions adaptées
• Explorez différentes catégories pour découvrir de nouveaux genres

Comment puis-je vous aider ?
"""
            
            elif intent == 'thanks':
                responses = [
                    "👍 Je vous en prie ! N'hésitez pas si vous avez besoin d'autre chose.",
                    "😊 Avec plaisir ! Bonne lecture !",
                    "🌟 Content d'avoir pu vous aider !",
                    "📚 De rien ! Bonne découverte littéraire !"
                ]
                import random
                response = random.choice(responses)
            
            elif intent == 'goodbye':
                responses = [
                    "👋 Au revoir ! Merci d'avoir utilisé Libro Online. À bientôt !",
                    "📖 Bonne lecture et à très bientôt !",
                    "🌟 Merci pour cette conversation ! Revenez quand vous voulez !",
                    "😊 Au revoir ! N'hésitez pas à revenir pour de nouvelles découvertes !"
                ]
                import random
                response = random.choice(responses)
            
            else:
                response = """
❓ Je n'ai pas bien compris votre demande. 

💡 **Voici ce que je peux faire pour vous :**

• 🔍 **Rechercher des livres** par titre, auteur ou sujet
• 💡 **Vous recommander** des livres par catégorie
• 📚 **Explorer les catégories** disponibles
• ⭐ **Découvrir les livres populaires**
• 📊 **Voir vos statistiques** (si connecté)
• 💰 **Trouver des livres** dans votre budget
• 🎯 **Obtenir des conseils** de lecture

🆕 **Essayez :**
• "recommande-moi un livre"
• "catégories disponibles" 
• "mes statistiques"
• "livres à moins de 40 TND"
• "conseils lecture"

Ou tapez 'aide' pour voir toutes les possibilités !
"""
        
        except Exception:
            response = "❌ Une erreur s'est produite lors du traitement de votre demande. Veuillez réessayer."
        
        # SAUVEGARDE
        try:
            self.save_conversation(user_message, response, self.user_email, session_id)
        except Exception:
            pass
        
        self.conversation_history.append(('assistant', response))
        
        return response


# Configuration de la base de données
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'gestion_biblio',
    'charset': 'utf8mb4',
    'autocommit': True
}


def main():
    print("=" * 60)
    print("🤖 ASSISTANT LIBRO ONLINE - Version Enrichie")
    print("=" * 60)
    print("Tapez 'quit', 'exit', ou 'au revoir' pour quitter\n")
    
    assistant = LibroAssistant(DB_CONFIG)
    
    try:
        while True:
            user_input = input("👤 Vous: ").strip()
            
            if user_input.lower() in ['quit', 'exit', 'au revoir']:
                print(f"\n🤖 Assistant: {assistant.respond(user_input)}")
                break
            
            if not user_input:
                continue
            
            response = assistant.respond(user_input)
            print(f"\n🤖 Assistant: {response}\n")
            print("-" * 60)
    
    except KeyboardInterrupt:
        print(f"\n\n🤖 Assistant: 👋 Au revoir !")
    finally:
        assistant.close_db()


if __name__ == "__main__":
    if len(sys.argv) > 1:
        user_message = sys.argv[1]
        user_email = sys.argv[2] if len(sys.argv) > 2 else 'anonymous'
        session_id = sys.argv[3] if len(sys.argv) > 3 else 'unknown'
        
        assistant = LibroAssistant(DB_CONFIG, user_email)
        response = assistant.respond(user_message, session_id)
        print(response)
        assistant.close_db()
    else:
        main()