export type Project = {
    id: number;
    name: string;
    description: string | null;
};

export type ProjectSummary = Project & {
    tasks_count: number;
    completed_tasks_count: number;
};
