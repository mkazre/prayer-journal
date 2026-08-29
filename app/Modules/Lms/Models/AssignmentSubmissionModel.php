<?php

namespace App\Modules\Lms\Models;

use CodeIgniter\Model;

class AssignmentSubmissionModel extends Model
{
    protected $table            = 'assignment_submissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'assignment_id', 'user_id', 'content', 'attachment_path', 'status',
        'points_awarded', 'feedback', 'graded_by', 'graded_at', 'submitted_at',
    ];

    public function submit(int $assignmentId, int $userId, string $content, ?string $attachmentPath = null): array
    {
        $existing = $this->where(['assignment_id' => $assignmentId, 'user_id' => $userId])->first();
        $data     = [
            'assignment_id'   => $assignmentId,
            'user_id'         => $userId,
            'content'         => $content,
            'attachment_path' => $attachmentPath,
            'status'          => 'submitted',
            'submitted_at'    => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->update($existing['id'], $data);

            return $this->find($existing['id']);
        }

        $id = $this->insert($data, true);

        return $this->find($id);
    }

    public function grade(int $submissionId, int $graderId, int $points, string $feedback): void
    {
        $this->update($submissionId, [
            'points_awarded' => $points,
            'feedback'       => $feedback,
            'status'         => 'graded',
            'graded_by'      => $graderId,
            'graded_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
