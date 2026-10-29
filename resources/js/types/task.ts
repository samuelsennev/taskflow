export type Task = {
    id: number;
    title: string;
    description: string | null;
    /** Due date (YYYY-MM-DD), sent only where tasks span several days. */
    due_date?: string;
    is_completed: boolean;
    /** Project the task belongs to, sent only where tasks span several projects. */
    project?: { id: number; name: string } | null;
};
