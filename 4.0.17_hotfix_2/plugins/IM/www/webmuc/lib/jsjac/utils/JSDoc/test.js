/** 
 * @fileoverview This file is to be used for testing the JSDoc parser
 * It is not intended to be an example of good JavaScript OO-programming,
 * nor is it intended to fulfill any specific purpose apart from 
 * demonstrating the functionality of the 
 * {@link http://sourceforge.net/projects/jsdoc JSDoc} parser
 *
 * @author Gabriel Reid gab_reid@users.sourceforge.net
 * @version 0.1 
 */


/**
 * Construct a new Shape object.
 * @class This is the basic Shape class.  
 * It can be considered an abstract class, even though no such thing
 * really existing in JavaScript
 * @constructor
 * @throws MemoryException if there is no more memory 
 * @throws GeneralShapeException rarely (if ever)
 * @return A new shape
 */
function Shape(){
  
   /**
    * This is an example of a function that is not given as a property
    * of a prototype, but instead it is assigned within a constructor.
    * For inner functions like this to be picked up by the parser, the
    * function that acts as a constructor <b>must</b> be denoted with
    * the <b>&#64;constructor</b> tag in its comment.
    * @type String
    */
   this.getClassName = function(){
      return "Shape";
   }

   /** 
    * This is a private method, just used here as an example 
    */
   function addReference(){
       // Do nothing...
   }
   
}

/**
 * Create a new Hexagon instance.
 * @extends Shape
 * @class Hexagon is a class that is a <i>logical</i> sublcass of 
 * {@link Shape} (thanks to the <code>&#64;extends</code> tag), but in 
 * reality it is completely unrelated to Shape.
 * @param {int} sideLength The length of one side for the new Hexagon
 */
function Hexagon(sideLength) {
}


/**
 * This is an unattached (static) function that adds two integers together.
 * @param {int} One The first number to add 
 * @param {int http://jsdoc.sourceforge.net/} Two The second number to add 
 * @author Gabriel Reid
 * @deprecated So you shouldn't use it anymore!
 */
function Add(One, Two){
    return One + Two;
}


/**
 * The color of this shape
 * @type Color
 */
Shape.prototype.color = null;

/**
 * The border of this shape. 
 * @type int
 */
Shape.prototype.border = null;

/* 
 * The assignment of function implementations for Shape, documentation will
 * be taken over from the method declaration.
 */

Shape.prototype.getCoords = Shape_GetCoords;

Shape.prototype.getColor = Shape_GetColor;

Shape.prototype.setCoords = Shape_SetCoords;

Shape.prototype.setColor = Shape_SetColor;

/*
 * These are all the instance method implementations for Shape
 */

/**
 * Get the coordinates of this shape. It is assumed that we're always talking
 * about shapes in a 2D location here.
 * @requires Shape The shape class
 * @returns A Coordinate object representing the location of this Shape
 * @type Coordinate
 */
function Shape_GetCoords(){
   return this.coords;
}

/**
 * Get the color of this shape.
 * @see #setColor
 * @type Color
 */
function Shape_GetColor(){
   return this.color;
}

/**
 * Set the coordinates for this Shape
 * @param {Coordinate} coordinates The coordinates to set for this Shape
 */
function Shape_SetCoords(coordinates){
   this.coords = coordinates;
}

/**
 * Set the color for this Shape
 * @param {Color} color The color to set for this Shape
 * @param other There is no other param, but it can still be documented if
 *              optional parameters are used
 * @throws NonExistantColorException (no, not really!)
 * @see #getColor
 */
function Shape_SetColor(color){
   this.color = color;
}

/**
 * Clone this shape
 * @returns A copy of this shape
 * @type Shape
 * @author Gabriel Reid
 */
Shape.prototype.clone = function(){
   return new Shape();
}

/**
 * Create a new Rectangle instance. 
 * @class A basic rectangle class, inherits from Shape.
 * This class could be considered a concrete implementation class
 * @constructor
 * @param {int} width The optional width for this Rectangle
 * @param {int} height Thie optional height for this Rectangle
 * @author Gabriel Reid
 * @see Shape Shape is the base class for this
 */
function Rectangle(width, // This is the width 
                  height // This is the height
                  ){
   if (width){
      this.width = width;
      if (height){
	 this.height = height;
      }
   }
}


/* Inherit from Shape */
Rectangle.prototype = new Shape();

/**
 * Value to represent the width of the Rectangle.
 * <br>Text in <b>bold</b> and <i>italic</i> and a 
 * link to <a href="http://sf.net">SourceForge</a>
 * @private
 * @type int
 */
Rectangle.prototype.width = 0;

/**
 * Value to represent the height of the Rectangle
 * @private
 * @type int
 */
Rectangle.prototype.height = 0;

/**
 * Get the type of this object. 
 * @type String
 */
Rectangle.prototype.getClassName= function(){
    return "Rectangle";
}

/*
 * These are all the instance method implementations for Rectangle 
 */

Rectangle.prototype.getWidth = Rectangle_GetWidth;

Rectangle.prototype.getHeight = Rectangle_GetHeight;

Rectangle.prototype.setWidth = Rectangle_SetWidth;

Rectangle.prototype.setHeight = Rectangle_SetHeight;

Rectangle.prototype.getArea = Rectangle_GetArea;


/**
 * Get the value of the width for the Rectangle
 * @type int
 * @see #setWidth
 */
function Rectangle_GetWidth(){
   return this.width;
}

/**
 * Get the value of the height for the Rectangle.
 * Another getter is the {@link Shape#getColor} method in the 
 * {@link Shape base Shape class}.  
 * @return The height of this Rectangle
 * @type int
 * @see #setHeight
 */
function Rectangle_GetHeight(){
    return this.height;
}

/**
 * Set the width value for this Rectangle.
 * @param {int} width The width value to be set
 * @see #getWidth
 */
function Rectangle_SetWidth(width){
   this.width = width;
}

/**
 * Set the height value for this Rectangle.
 * @param {int} height The height value to be set
 * @see #getHeight
 */
function Rectangle_SetHeight(height){
   this.height = height;
}

/**
 * Get the value for the total area of this Rectangle
 * @return total area of this Rectangle
 * @type int
 */
function Rectangle_GetArea(){
   return width * height;
}


/**
 * Create a new Square instance.
 * @class A Square is a subclass of {@link Rectangle}
 * @param {int} width The optional width for this Rectangle
 * @param {int} height The optional height for this Rectangle
 */
function Square(width, height){
   if (width){
      this.width = width;
      if (height){
	 this.height = height;
      }
   } 
   
}

/* Square is a subclass of Rectangle */
Square.prototype = new Rectangle();


/*
 * The assignment of function implementation for Shape.
 */
Square.prototype.setWidth = Square_SetWidth;

Square.prototype.setHeight = Square_SetHeight;



/**
 * Set the width value for this Square.
 * @param {int} width The width value to be set
 * @see #getWidth
 */
function Square_SetWidth(width){
   this.width = this.height = width;
}

/**
 * Set the height value for this Square 
 * Sets the {@link Rectangle#height height} attribute in the Rectangle.
 * @param {int} height The height value to be set
 */
function Square_SetHeight(height){
   this.height = this.width = height;
}


/**
 * Create a new Circle instance based on a radius.
 * @class Circle class is another subclass of Shape
 * @param {int} radius The optional radius of this Circle
 */
function Circle(radius){
   if (radius){
      this.radius = radius;
   }
}

/* Circle inherits from Shape */
Circle.prototype = new Shape();

/** 
 * The radius value for this Circle 
 * @private
 * @type int
 */
Circle.prototype.radius = 0;

/** 
 * A very simple class (static) field that is also a constant
 * @final
 * @type float
 */
Circle.PI = 3.14;

Circle.createCircle = Circle_CreateCircle;

Circle.prototype.getRadius = Circle_GetRadius;

Circle.prototype.setRadius = Circle_SetRadius;

/**
 * Get the radius value for this Circle
 * @type int
 * @see #setRadius
 */
function Circle_GetRadius(){
   return this.radius;
}

/** 
 * Set the radius value for this Circle
 * @param {int} radius The radius value to set
 * @see #getRadius
 */
function Circle_SetRadius(radius){
   this.radius = radius;
}

/** 
 * An example of a  class (static) method that acts as a factory for Circle
 * objects. Given a radius value, this method creates a new Circle.
 * @param {int} radius The radius value to use for the new Circle.
 * @type Circle
 */
function Circle_CreateCircle(radius){
    return new Circle(radius);
}


/**
 * Create a new Coordinate instance based on x and y grid data.
 * @class Coordinate is a class that can encapsulate location information.
 * @param {int} x The optional x portion of the Coordinate
 * @param {int} y The optinal y portion of the Coordinate
 */
function Coordinate(x, y){
   if (x){
      this.x = x;
      if (y){
	 this.y = y;
      }
   }
}

/** 
 * The x portion of the Coordinate 
 * @type int
 * @see #getX
 * @see #setX
 */
Coordinate.prototype.x = 0;

/** 
 * The y portion of the Coordinate 
 * @type int
 * @see #getY
 * @see #setY
 */
Coordinate.prototype.y = 0;

Coordinate.prototype.getX = Coordinate_GetX;
Coordinate.prototype.getY = Coordinate_GetY;
Coordinate.prototype.setX = Coordinate_SetX;
Coordinate.prototype.setY = Coordinate_SetY;

/**
 * Gets the x portion of the Coordinate.
 * @type int
 * @see #setX
 */
function Coordinate_GetX(){
   return this.x;
}

/** 
 * Get the y portion of the Coordinate.
 * @type int
 * @see #setY
 */
function Coordinate_GetY(){
   return this.y;
}

/**
 * Sets the x portion of the Coordinate.
 * @param {int} x The x value to set
 * @see #getX
 */
function Coordinate_SetX(x){
   this.x = x;
}

/** 
 * Sets the y portion of the Coordinate.
 * @param {int} y The y value to set
 * @see #getY
 */
function Coordinate_SetY(y){
   this.y = y;
}

/**
 * @class This class exists to demonstrate the assignment of a class prototype
 * as an anonymous block.
 */
function ShapeFactory(){
}

ShapeFactory.prototype = {
   /** 
    * Creates a new {@link Shape} instance.
    * @return A new {@link Shape}
    * @type Shape
    */
   createShape: function(){
      return new Shape();
   }
}

/**
 * An example of a singleton class
 */
MySingletonShapeFactory = new function(){

   /**
    * Get the next {@link Shape} 
    * @type Shape
    * @return A new {@link Shape}
    */
   this.getShape = function(){ 
      return null; 
   }

}


/** 
 * Create a new Foo instance.
 * @class This is the Foo class. It exists to demonstrate 'nested' classes.
 * @constructor 
 * @see Foo.Bar
 */
function Foo(){}

/** 
 * Creates a new instance of Bar.
 * @class This class exists to demonstrate 'nested' classes.
 * @constructor 
 * @see Foo.Bar
 */
function Bar(){}

/** 
 * Nested class
 * @constructor 
 */
Foo.Bar = function(){this.x = 2;}

Foo.Bar.prototype = new Bar();

Foo.Bar.prototype.y = '3';

