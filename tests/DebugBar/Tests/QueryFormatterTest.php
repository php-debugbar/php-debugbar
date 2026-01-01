<?php

declare(strict_types=1);

namespace DebugBar\Tests;

use DebugBar\DataCollector\PDO\TracedStatement;
use DebugBar\DataFormatter\QueryFormatter;

/**
 * Class TracedStatementTest
 *
 * @package DebugBar\Tests
 */
class QueryFormatterTest extends DebugBarTestCase
{
    /**
     * Check if query parameters are being replaced in the correct way
     *
     * @bugFix Before fix it : select *
     *                          from geral.exame_part ep
     *                           where ep.id_exame = <1> and
     *                             ep.id_exame_situacao = <2>'
     *                            ep.id_exame_situacao = <1>_situacao
     *
     */
    public function testReplacementParamsQuery(): void
    {
        $formatter = new QueryFormatter();

        $sql = 'select *
                from geral.exame_part ep
                where ep.id_exame = :id_exame and 
                      ep.id_exame_situacao = :id_exame_situacao';
        $params = [
            ':id_exame'          => 1,
            ':id_exame_situacao' => 2,
        ];

        $expected = 'select *
                from geral.exame_part ep
                where ep.id_exame = 1 and 
                      ep.id_exame_situacao = 2';
        $result = $formatter->formatSqlWithBindings($sql, $params);
        $this->assertEquals($expected, $result);
    }

    public function testReplacementParamsQueryWithPdo(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $formatter = new QueryFormatter();

        $sql = 'select * from users where id = :id and name = :name'; ;
        $params = [
            ':id' => 1,
            ':name' => 'Barry',
        ];

        $expected = "select * from users where id = 1 and name = 'Barry'";
        $result = $formatter->formatSqlWithBindings($sql, $params, $pdo);
        $this->assertEquals($expected, $result);
    }

    public function testReplacementParamsQueryWithMockPdo(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $pdo->expects($this->once())->method('quote')->willReturnCallback(function ($value) {
            return "'$value'";
        });

        $formatter = new QueryFormatter();

        $sql = 'select * from users where id = :id and name = :name'; ;
        $params = [
            ':id' => 1,
            ':name' => 'Barry',
        ];

        $expected = "select * from users where id = 1 and name = 'Barry'";
        $result = $formatter->formatSqlWithBindings($sql, $params, $pdo);
        $this->assertEquals($expected, $result);


    }

    public function testReplacementParamsContainingBackReferenceSyntaxGeneratesCorrectString(): void
    {
        $formatter = new QueryFormatter();

        $hashedPassword = '$2y$10$S3Y/kSsx8Z5BPtdd9.k3LOkbQ0egtsUHBT9EGQ.spxsmaEWbrxBW2';
        $sql = "UPDATE user SET password = :password";

        $params = [
            ':password' => $hashedPassword,
        ];

        $result = $formatter->formatSqlWithBindings($sql, $params);

        $expected = "UPDATE user SET password = '$hashedPassword'";

        $this->assertEquals($expected, $result);
    }

    public function testReplacementParamsContainingPotentialAdditionalQuestionMarkPlaceholderGeneratesCorrectString(): void
    {
        $formatter = new QueryFormatter();

        $hasQuestionMark = "Asking a question?";
        $string = "Asking for a friend";

        $sql = "INSERT INTO questions SET question = ?, detail = ?";

        $params = [$hasQuestionMark, $string];

        $result = $formatter->formatSqlWithBindings($sql, $params);

        $expected = "INSERT INTO questions SET question = '$hasQuestionMark', detail = '$string'";

        $this->assertEquals($expected, $result);
    }

    public function testReplacementParamsContainingPotentialAdditionalNamedPlaceholderGeneratesCorrectString(): void
    {
        $formatter = new QueryFormatter();

        $hasQuestionMark = "Asking a question with a :string inside";
        $string = "Asking for a friend";

        $sql = "INSERT INTO questions SET question = :question, detail = :string";

        $params = [
            ':question' => $hasQuestionMark,
            ':string'   => $string,
        ];

        $result = $formatter->formatSqlWithBindings($sql, $params);

        $expected = "INSERT INTO questions SET question = '$hasQuestionMark', detail = '$string'";

        $this->assertEquals($expected, $result);
    }

    /**
     * Check if literal `NULL` query parameters are replaced without triggering a deprecation warning since PHP 8.0.0.
     * This can happen when e.g. binding `PDO::PARAM_NULL` to your prepared statement.
     *
     * @link https://www.php.net/manual/en/migration81.deprecated.php#migration81.deprecated.core.null-not-nullable-internal
     */
    public function testReplacementParamsContainingLiteralNullValueGeneratesCorrectString(): void
    {
        $formatter = new QueryFormatter();

        $sql = 'UPDATE user SET login_failed_reason = :nullable_reason WHERE id = :id';

        $params = [
            'id' => 1234,
            'nullable_reason' => 'Life happens',
        ];

        $expected = "UPDATE user SET login_failed_reason = 'Life happens' WHERE id = 1234";
        $result = $formatter->formatSqlWithBindings($sql, $params);
        $this->assertEquals($expected, $result);

        $params = [
            'id' => 1234,
            'nullable_reason' => null,
        ];

        $expected = 'UPDATE user SET login_failed_reason = NULL WHERE id = 1234';
        $result = $formatter->formatSqlWithBindings($sql, $params);
        $this->assertEquals($expected, $result);
    }

    /**
     * Check if query parameters are being replaced in the correct way
     *
     * @bugFix Before fix it : select *
     *                          from geral.person p
     *                           left join geral.contract c
     *                             on c.id_person = p.id_person
     *                           where c.status = <1> and
     *                           p.status <> :status;
     *
     */
    public function testRepeatParamsQuery(): void
    {
        $formatter = new QueryFormatter();

        $sql = 'select *
                from geral.person p
                left join geral.contract c
                  on c.id_person = p.id_person
                where c.status = :status and 
                      p.status <> :status';
        $params = [
            ':status' => 1,
        ];
        $expected = 'select *
                from geral.person p
                left join geral.contract c
                  on c.id_person = p.id_person
                where c.status = 1 and 
                      p.status <> 1';
        $result = $formatter->formatSqlWithBindings($sql, $params);
        $this->assertEquals($expected, $result);
    }

    /**
     * Check that query parameters are being replaced only once
     *
     * @bugFix Before fix it: select * from
     *                          `my_table` where `my_field` between
     *                           <2018-01-01> and <2018-01-01>
     *
     */
    public function testParametersAreNotRepeated(): void
    {
        $formatter = new QueryFormatter();

        $query = 'select * from `my_table` where `my_field` between ? and ?';
        $bindings = [
            '2018-01-01',
            '2020-09-01',
        ];

        $this->assertEquals(
            "select * from `my_table` where `my_field` between '2018-01-01' and '2020-09-01'",
            $formatter->formatSqlWithBindings($query, $bindings)
        );
    }
}
