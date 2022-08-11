<?php

namespace Tests\Feature\Http\Accounting;

use App\Model\Accounting\MemoJournal;
use Tests\TestCase;

class MemoJournalApprovalTest extends TestCase
{
    use MemoJournalSetup;
    
    public static $path = '/api/v1/accounting/approval/memo-journals';

    /**
     * @test 
     */
    public function read_all_memo_journal_approval()
    {
        $this->createMemoJournal();
        
        $response = $this->json('GET', self::$path, [
            'limit' => '10',
            'page' => '1',
        ], $this->headers);

        $response->assertStatus(200);
    }

    /**
     * @test 
     */
    public function unauthorized_approve_memo_journal()
    {
        $this->createMemoJournal();

        $memoJournal = MemoJournal::orderBy('id', 'asc')->first();

        $response = $this->json('POST', '/api/v1/accounting/memo-journals/'.$memoJournal->id.'/approve', [
            'id' => $memoJournal->id
        ], $this->headers);
        
        $response->assertStatus(500)
            ->assertJson([
                "code" => 500,
                "message" => "Internal Server Error"
            ]);
    }

    /**
     * @test 
     */
    public function success_approve_memo_journal()
    {
        $this->setApprovePermission();

        $this->createMemoJournal();

        $memoJournal = MemoJournal::orderBy('id', 'asc')->first();

        $response = $this->json('POST', '/api/v1/accounting/memo-journals/'.$memoJournal->id.'/approve', [
            'id' => $memoJournal->id
        ], $this->headers);
        
        $response->assertStatus(200);
    }

    /**
     * @test 
     */
    public function unauthorized_reject_memo_journal()
    {
        $this->createMemoJournal();

        $memoJournal = MemoJournal::orderBy('id', 'desc')->first();

        $response = $this->json('POST', '/api/v1/accounting/memo-journals/'.$memoJournal->id.'/reject', [
            'id' => $memoJournal->id,
            'reason' => 'some reason'
        ], $this->headers);
        
        $response->assertStatus(500)
            ->assertJson([
                "code" => 500,
                "message" => "Internal Server Error"
            ]);
    }

    /**
     * @test 
     */
    public function invalid_reject_memo_journal()
    {
        $this->setApprovePermission();

        $this->createMemoJournal();

        $memoJournal = MemoJournal::orderBy('id', 'desc')->first();

        $response = $this->json('POST', '/api/v1/accounting/memo-journals/'.$memoJournal->id.'/reject', [], $this->headers);
        
        $response->assertStatus(422);
    }

    /**
     * @test 
     */
    public function success_reject_memo_journal()
    {
        $this->setApprovePermission();

        $this->createMemoJournal();

        $memoJournal = MemoJournal::orderBy('id', 'desc')->first();

        $response = $this->json('POST', '/api/v1/accounting/memo-journals/'.$memoJournal->id.'/reject', [
            'id' => $memoJournal->id,
            'reason' => 'some reason'
        ], $this->headers);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function send_memo_journal_approval()
    {
        $this->createMemoJournal();

        $memoJournal = MemoJournal::orderBy('id', 'desc')->first();

        $data = [
            "ids" => [
                "id" => $memoJournal->id,
            ],
        ];

        $response = $this->json('POST', self::$path.'/send', $data, $this->headers);
        
        $response->assertStatus(200);
    }
}
