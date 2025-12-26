CREATE TABLE cursos_alumnos(
	cursoId INT,
    userId INT,
    FOREIGN KEY (cursoId) REFERENCES cursos(idCurso),
    FOREIGN KEY (userId) REFERENCES users(idUser)
);