<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260103150737 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, note_id INT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, publication_date DATETIME NOT NULL, is_edited TINYINT(1) DEFAULT NULL, up_vote INT NOT NULL, INDEX IDX_9474526C26ED0855 (note_id), INDEX IDX_9474526CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment_reply (id INT AUTO_INCREMENT NOT NULL, comment_id INT DEFAULT NULL, user_id INT NOT NULL, message VARCHAR(255) NOT NULL, publication_date DATETIME NOT NULL, is_edited TINYINT(1) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, INDEX IDX_54325E11F8697D13 (comment_id), INDEX IDX_54325E11A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment_reply_report (id INT AUTO_INCREMENT NOT NULL, reply_id INT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, creation_date DATETIME NOT NULL, reporter_id INT NOT NULL, INDEX IDX_55BF1DE98A0E4E7F (reply_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment_reply_vote (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, reply_id INT NOT NULL, is_upvoted TINYINT(1) NOT NULL, INDEX IDX_885F9A72A76ED395 (user_id), INDEX IDX_885F9A728A0E4E7F (reply_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment_report (id INT AUTO_INCREMENT NOT NULL, comment_id INT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, creation_date DATETIME NOT NULL, reporter_id INT NOT NULL, INDEX IDX_E3C2F96F8697D13 (comment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE comment_vote (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, note_id INT NOT NULL, comment_id INT NOT NULL, is_upvoted TINYINT(1) NOT NULL, INDEX IDX_7C262788A76ED395 (user_id), INDEX IDX_7C26278826ED0855 (note_id), INDEX IDX_7C262788F8697D13 (comment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE friend_request (id INT AUTO_INCREMENT NOT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, INDEX IDX_F284D94F624B39D (sender_id), INDEX IDX_F284D94CD53EDB6 (receiver_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE interest (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, INDEX IDX_6C3E1A67A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, mentioned_user_id INT DEFAULT NULL, ring_id INT DEFAULT NULL, content LONGTEXT NOT NULL, up_vote INT NOT NULL, down_vote INT NOT NULL, is_pinned TINYINT(1) NOT NULL, pinned_at DATETIME DEFAULT NULL, nametag VARCHAR(255) DEFAULT NULL, publication_date DATETIME NOT NULL, image VARCHAR(255) DEFAULT NULL, is_edited TINYINT(1) DEFAULT NULL, is_from_ring TINYINT(1) DEFAULT NULL, INDEX IDX_CFBDFA14A76ED395 (user_id), INDEX IDX_CFBDFA14E6655814 (mentioned_user_id), INDEX IDX_CFBDFA14D0935A5A (ring_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note_report (id INT AUTO_INCREMENT NOT NULL, note_id INT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, creation_date DATETIME NOT NULL, reporter_id INT NOT NULL, INDEX IDX_DCFD205026ED0855 (note_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE note_vote (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, note_id INT NOT NULL, is_upvoted TINYINT(1) DEFAULT NULL, is_downvoted TINYINT(1) DEFAULT NULL, INDEX IDX_FA6A943CA76ED395 (user_id), INDEX IDX_FA6A943C26ED0855 (note_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, note_id INT DEFAULT NULL, comment_id INT DEFAULT NULL, sender_id INT DEFAULT NULL, receiver_id INT NOT NULL, friend_request_id INT DEFAULT NULL, ring_id INT DEFAULT NULL, ticket_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, notified_date DATETIME NOT NULL, is_read TINYINT(1) NOT NULL, INDEX IDX_BF5476CA26ED0855 (note_id), INDEX IDX_BF5476CAF8697D13 (comment_id), INDEX IDX_BF5476CAF624B39D (sender_id), INDEX IDX_BF5476CACD53EDB6 (receiver_id), UNIQUE INDEX UNIQ_BF5476CAEC394CA1 (friend_request_id), INDEX IDX_BF5476CAD0935A5A (ring_id), INDEX IDX_BF5476CA700047D2 (ticket_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ring (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, interest_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, banner VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, is_suspended INT NOT NULL, suspension_reason VARCHAR(255) DEFAULT NULL, INDEX IDX_8FDCF576A76ED395 (user_id), INDEX IDX_8FDCF5765A95FF89 (interest_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ring_members (id INT AUTO_INCREMENT NOT NULL, ring_id INT NOT NULL, user_id INT DEFAULT NULL, role VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, joined_at DATETIME NOT NULL, INDEX IDX_3BDB45FCD0935A5A (ring_id), INDEX IDX_3BDB45FCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ticket (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, content VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, creation_date DATETIME NOT NULL, admin_reply LONGTEXT DEFAULT NULL, INDEX IDX_97A0ADA3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, creation_date DATE NOT NULL, nametag VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, avatar VARCHAR(255) NOT NULL, is_banned TINYINT(1) NOT NULL, bio LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_friends (user_id INT NOT NULL, friend_id INT NOT NULL, INDEX IDX_79E36E63A76ED395 (user_id), INDEX IDX_79E36E636A5458E8 (friend_id), PRIMARY KEY(user_id, friend_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_favorite_notes (user_id INT NOT NULL, note_id INT NOT NULL, INDEX IDX_DC1652F1A76ED395 (user_id), INDEX IDX_DC1652F126ED0855 (note_id), PRIMARY KEY(user_id, note_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C26ED0855 FOREIGN KEY (note_id) REFERENCES note (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_reply ADD CONSTRAINT FK_54325E11F8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id)');
        $this->addSql('ALTER TABLE comment_reply ADD CONSTRAINT FK_54325E11A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comment_reply_report ADD CONSTRAINT FK_55BF1DE98A0E4E7F FOREIGN KEY (reply_id) REFERENCES comment_reply (id)');
        $this->addSql('ALTER TABLE comment_reply_vote ADD CONSTRAINT FK_885F9A72A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comment_reply_vote ADD CONSTRAINT FK_885F9A728A0E4E7F FOREIGN KEY (reply_id) REFERENCES comment_reply (id)');
        $this->addSql('ALTER TABLE comment_report ADD CONSTRAINT FK_E3C2F96F8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_vote ADD CONSTRAINT FK_7C262788A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment_vote ADD CONSTRAINT FK_7C26278826ED0855 FOREIGN KEY (note_id) REFERENCES note (id)');
        $this->addSql('ALTER TABLE comment_vote ADD CONSTRAINT FK_7C262788F8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE friend_request ADD CONSTRAINT FK_F284D94F624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE friend_request ADD CONSTRAINT FK_F284D94CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interest ADD CONSTRAINT FK_6C3E1A67A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14E6655814 FOREIGN KEY (mentioned_user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14D0935A5A FOREIGN KEY (ring_id) REFERENCES ring (id)');
        $this->addSql('ALTER TABLE note_report ADD CONSTRAINT FK_DCFD205026ED0855 FOREIGN KEY (note_id) REFERENCES note (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE note_vote ADD CONSTRAINT FK_FA6A943CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE note_vote ADD CONSTRAINT FK_FA6A943C26ED0855 FOREIGN KEY (note_id) REFERENCES note (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA26ED0855 FOREIGN KEY (note_id) REFERENCES note (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF8697D13 FOREIGN KEY (comment_id) REFERENCES comment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAF624B39D FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CACD53EDB6 FOREIGN KEY (receiver_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAEC394CA1 FOREIGN KEY (friend_request_id) REFERENCES friend_request (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAD0935A5A FOREIGN KEY (ring_id) REFERENCES ring (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA700047D2 FOREIGN KEY (ticket_id) REFERENCES ticket (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ring ADD CONSTRAINT FK_8FDCF576A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ring ADD CONSTRAINT FK_8FDCF5765A95FF89 FOREIGN KEY (interest_id) REFERENCES interest (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ring_members ADD CONSTRAINT FK_3BDB45FCD0935A5A FOREIGN KEY (ring_id) REFERENCES ring (id)');
        $this->addSql('ALTER TABLE ring_members ADD CONSTRAINT FK_3BDB45FCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ticket ADD CONSTRAINT FK_97A0ADA3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_friends ADD CONSTRAINT FK_79E36E63A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_friends ADD CONSTRAINT FK_79E36E636A5458E8 FOREIGN KEY (friend_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_favorite_notes ADD CONSTRAINT FK_DC1652F1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_favorite_notes ADD CONSTRAINT FK_DC1652F126ED0855 FOREIGN KEY (note_id) REFERENCES note (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C26ED0855');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE comment_reply DROP FOREIGN KEY FK_54325E11F8697D13');
        $this->addSql('ALTER TABLE comment_reply DROP FOREIGN KEY FK_54325E11A76ED395');
        $this->addSql('ALTER TABLE comment_reply_report DROP FOREIGN KEY FK_55BF1DE98A0E4E7F');
        $this->addSql('ALTER TABLE comment_reply_vote DROP FOREIGN KEY FK_885F9A72A76ED395');
        $this->addSql('ALTER TABLE comment_reply_vote DROP FOREIGN KEY FK_885F9A728A0E4E7F');
        $this->addSql('ALTER TABLE comment_report DROP FOREIGN KEY FK_E3C2F96F8697D13');
        $this->addSql('ALTER TABLE comment_vote DROP FOREIGN KEY FK_7C262788A76ED395');
        $this->addSql('ALTER TABLE comment_vote DROP FOREIGN KEY FK_7C26278826ED0855');
        $this->addSql('ALTER TABLE comment_vote DROP FOREIGN KEY FK_7C262788F8697D13');
        $this->addSql('ALTER TABLE friend_request DROP FOREIGN KEY FK_F284D94F624B39D');
        $this->addSql('ALTER TABLE friend_request DROP FOREIGN KEY FK_F284D94CD53EDB6');
        $this->addSql('ALTER TABLE interest DROP FOREIGN KEY FK_6C3E1A67A76ED395');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14A76ED395');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14E6655814');
        $this->addSql('ALTER TABLE note DROP FOREIGN KEY FK_CFBDFA14D0935A5A');
        $this->addSql('ALTER TABLE note_report DROP FOREIGN KEY FK_DCFD205026ED0855');
        $this->addSql('ALTER TABLE note_vote DROP FOREIGN KEY FK_FA6A943CA76ED395');
        $this->addSql('ALTER TABLE note_vote DROP FOREIGN KEY FK_FA6A943C26ED0855');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA26ED0855');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAF8697D13');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAF624B39D');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CACD53EDB6');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAEC394CA1');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAD0935A5A');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA700047D2');
        $this->addSql('ALTER TABLE ring DROP FOREIGN KEY FK_8FDCF576A76ED395');
        $this->addSql('ALTER TABLE ring DROP FOREIGN KEY FK_8FDCF5765A95FF89');
        $this->addSql('ALTER TABLE ring_members DROP FOREIGN KEY FK_3BDB45FCD0935A5A');
        $this->addSql('ALTER TABLE ring_members DROP FOREIGN KEY FK_3BDB45FCA76ED395');
        $this->addSql('ALTER TABLE ticket DROP FOREIGN KEY FK_97A0ADA3A76ED395');
        $this->addSql('ALTER TABLE user_friends DROP FOREIGN KEY FK_79E36E63A76ED395');
        $this->addSql('ALTER TABLE user_friends DROP FOREIGN KEY FK_79E36E636A5458E8');
        $this->addSql('ALTER TABLE user_favorite_notes DROP FOREIGN KEY FK_DC1652F1A76ED395');
        $this->addSql('ALTER TABLE user_favorite_notes DROP FOREIGN KEY FK_DC1652F126ED0855');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE comment_reply');
        $this->addSql('DROP TABLE comment_reply_report');
        $this->addSql('DROP TABLE comment_reply_vote');
        $this->addSql('DROP TABLE comment_report');
        $this->addSql('DROP TABLE comment_vote');
        $this->addSql('DROP TABLE friend_request');
        $this->addSql('DROP TABLE interest');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP TABLE note_report');
        $this->addSql('DROP TABLE note_vote');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE ring');
        $this->addSql('DROP TABLE ring_members');
        $this->addSql('DROP TABLE ticket');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_friends');
        $this->addSql('DROP TABLE user_favorite_notes');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
