<?php

class SchoolQuery
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Get schools with pagination, search, and sorting
     */
    public function getSchools($params = [])
    {
        $search = $params['search'] ?? '';
        $limit = (int)($params['limit'] ?? 10);
        $page = (int)($params['page'] ?? 1);
        $sort = $params['sort'] ?? 'school_name';
        $order = strtoupper($params['order'] ?? 'ASC');
        
        $offset = ($page - 1) * $limit;

        // Validate sort column
        $allowedSorts = [
            'division_office', 'school_name', 'address', 'permit_no', 
            'program_offering', 'contact_person', 'founding_year', 'created_at'
        ];
        
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'school_name';
        }

        // Validate order
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'ASC';
        }

        try {
            // Build query based on search
            if (!empty($search)) {
                $query = "SELECT * FROM schools WHERE 
                        division_office LIKE :search OR 
                        school_name LIKE :search OR 
                        address LIKE :search OR 
                        permit_no LIKE :search OR 
                        program_offering LIKE :search OR 
                        contact_person LIKE :search OR
                        school_description LIKE :search
                        ORDER BY {$sort} {$order} 
                        LIMIT :limit OFFSET :offset";
                
                $stmt = $this->db->connection->prepare($query);
                $searchTerm = "%{$search}%";
                $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $query = "SELECT * FROM schools ORDER BY {$sort} {$order} LIMIT :limit OFFSET :offset";
                $stmt = $this->db->connection->prepare($query);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
            }

            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count for pagination
            if (!empty($search)) {
                $countQuery = "SELECT COUNT(*) as total FROM schools WHERE 
                             division_office LIKE :search OR 
                             school_name LIKE :search OR 
                             address LIKE :search OR 
                             permit_no LIKE :search OR 
                             program_offering LIKE :search OR 
                             contact_person LIKE :search OR
                             school_description LIKE :search";
                $countStmt = $this->db->connection->prepare($countQuery);
                $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
            } else {
                $countQuery = "SELECT COUNT(*) as total FROM schools";
                $countStmt = $this->db->connection->prepare($countQuery);
            }
            
            $countStmt->execute();
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            $totalPages = ceil($totalRecords / $limit);

            return [
                'schools' => $schools,
                'totalRecords' => $totalRecords,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'limit' => $limit
            ];

        } catch (Exception $e) {
            throw new Exception("Error fetching schools: " . $e->getMessage());
        }
    }

    /**
     * Get a single school by ID
     */
    public function getSchoolById($id)
    {
        try {
            $query = "SELECT * FROM schools WHERE id = :id";
            $stmt = $this->db->connection->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Error fetching school: " . $e->getMessage());
        }
    }

    /**
     * Update school information
     */
    public function updateSchool($id, $data)
    {
        try {
            $updateFields = [];
            $allowedFields = [
                'division_office', 'school_name', 'address', 'permit_no', 'program_offering',
                'contact_phone', 'contact_email', 'contact_person', 'school_description',
                'school_history', 'mission_statement', 'vision_statement', 'founding_year',
                'accreditation', 'recognition', 'website_url', 'facebook_url',
                'student_population', 'faculty_count', 'facilities', 'achievements'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = :{$field}";
                }
            }

            if (empty($updateFields)) {
                throw new Exception("No valid fields to update");
            }

            $query = "UPDATE schools SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = :id";
            
            $stmt = $this->db->connection->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $stmt->bindParam(":{$field}", $data[$field]);
                }
            }

            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Error updating school: " . $e->getMessage());
        }
    }

    /**
     * Create a new school
     */
    public function createSchool($data)
    {
        try {
            $fields = [];
            $values = [];
            $allowedFields = [
                'division_office', 'school_name', 'address', 'permit_no', 'program_offering',
                'contact_phone', 'contact_email', 'contact_person', 'school_description',
                'school_history', 'mission_statement', 'vision_statement', 'founding_year',
                'accreditation', 'recognition', 'website_url', 'facebook_url',
                'student_population', 'faculty_count', 'facilities', 'achievements'
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field]) && $data[$field] !== '') {
                    $fields[] = $field;
                    $values[] = ":{$field}";
                }
            }

            if (empty($fields)) {
                throw new Exception("No valid data provided");
            }

            $query = "INSERT INTO schools (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
            
            $stmt = $this->db->connection->prepare($query);
            
            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $stmt->bindParam(":{$field}", $data[$field]);
                }
            }

            $stmt->execute();
            return $this->db->connection->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error creating school: " . $e->getMessage());
        }
    }

    /**
     * Delete a school
     */
    public function deleteSchool($id)
    {
        try {
            $query = "DELETE FROM schools WHERE id = :id";
            $stmt = $this->db->connection->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Error deleting school: " . $e->getMessage());
        }
    }

    /**
     * Get school statistics
     */
    public function getStatistics()
    {
        try {
            $stats = [];

            // Total schools
            $query = "SELECT COUNT(*) as total FROM schools";
            $stmt = $this->db->connection->prepare($query);
            $stmt->execute();
            $stats['total_schools'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Schools by program offering
            $query = "SELECT program_offering, COUNT(*) as count FROM schools GROUP BY program_offering ORDER BY count DESC";
            $stmt = $this->db->connection->prepare($query);
            $stmt->execute();
            $stats['by_program'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Schools by division office
            $query = "SELECT division_office, COUNT(*) as count FROM schools GROUP BY division_office ORDER BY count DESC";
            $stmt = $this->db->connection->prepare($query);
            $stmt->execute();
            $stats['by_division'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Average founding year
            $query = "SELECT AVG(founding_year) as avg_year FROM schools WHERE founding_year IS NOT NULL";
            $stmt = $this->db->connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['avg_founding_year'] = $result['avg_year'] ? round($result['avg_year']) : null;

            return $stats;
        } catch (Exception $e) {
            throw new Exception("Error getting statistics: " . $e->getMessage());
        }
    }
}